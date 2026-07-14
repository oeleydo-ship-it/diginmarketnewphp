<?php
namespace App\Services;
use App\Contracts\PayoutGateway;
use App\Models\AuditLog;
use App\Models\SellerWallet;
use App\Models\StripeConnectedAccount;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class WithdrawalService
{
 public function __construct(private PayoutGateway $gateway){}
 public function approve(WithdrawalRequest $withdrawal,User $admin,string $note=''):void
 {
  if(!in_array($withdrawal->status,['pending','under_review'],true))return;
  $account=StripeConnectedAccount::where('seller_id',$withdrawal->seller_id)->first();
  if(!$account||!$account->payouts_enabled)throw ValidationException::withMessages(['withdrawal'=>'Seller does not have a payout-enabled Stripe account.']);
  $wallet=SellerWallet::findOrFail($withdrawal->seller_wallet_id);
  $transfer=$this->gateway->transfer($withdrawal,$account->stripe_account_id,$wallet->currency);
  DB::transaction(function()use($withdrawal,$admin,$note,$transfer){$withdrawal=WithdrawalRequest::lockForUpdate()->findOrFail($withdrawal->id);if(!in_array($withdrawal->status,['pending','under_review'],true))return;$wallet=SellerWallet::lockForUpdate()->findOrFail($withdrawal->seller_wallet_id);$wallet->decrement('reserved_balance',(float)$withdrawal->amount);$wallet->increment('withdrawn_balance',(float)$withdrawal->amount);$withdrawal->update(['status'=>'paid','administrator_note'=>$note,'stripe_transfer_id'=>$transfer['id'],'processed_at'=>now()]);$wallet->transactions()->create(['uuid'=>(string)str()->uuid(),'type'=>'withdrawal_paid','balance_bucket'=>'reserved','amount'=>-(float)$withdrawal->amount,'currency'=>$wallet->currency,'reference'=>'withdrawal:'.$withdrawal->id.':paid','metadata'=>['stripe_transfer_id'=>$transfer['id'],'approved_by'=>$admin->id],'created_at'=>now()]);AuditLog::create(['user_id'=>$admin->id,'action'=>'withdrawal.paid','entity_type'=>WithdrawalRequest::class,'entity_id'=>$withdrawal->id,'old_values'=>['status'=>'pending'],'new_values'=>['status'=>'paid','stripe_transfer_id'=>$transfer['id']],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);});
  if($withdrawal->fresh()->status==='paid')app(MarketplaceMailer::class)->withdrawalDecided($withdrawal->fresh());
 }
 public function request(SellerWallet $wallet,float $amount):WithdrawalRequest
 {
  return DB::transaction(function()use($wallet,$amount){$wallet=SellerWallet::lockForUpdate()->findOrFail($wallet->id);$minimum=(float)config('marketplace.minimum_withdrawal',50);if($amount<$minimum||$amount>(float)$wallet->available_balance)throw ValidationException::withMessages(['amount'=>'Amount is below the minimum or exceeds available balance.']);$fee=round(max(0,$amount*(float)config('marketplace.withdrawal_fee_rate',0)/100),2);$withdrawal=WithdrawalRequest::create(['number'=>'WD-'.now()->format('Ymd').'-'.str()->upper(str()->random(8)),'seller_id'=>$wallet->seller_id,'seller_wallet_id'=>$wallet->id,'amount'=>$amount,'fee'=>$fee,'net_amount'=>$amount-$fee,'status'=>'pending']);$wallet->decrement('available_balance',$amount);$wallet->increment('reserved_balance',$amount);$wallet->transactions()->create(['uuid'=>(string)str()->uuid(),'type'=>'withdrawal_reserve','balance_bucket'=>'available','amount'=>-$amount,'currency'=>$wallet->currency,'reference'=>'withdrawal:'.$withdrawal->id.':reserve','metadata'=>['withdrawal_number'=>$withdrawal->number],'created_at'=>now()]);return $withdrawal;});
 }
 public function reject(WithdrawalRequest $withdrawal,string $note=''):void
 {
  $wasOpen=in_array($withdrawal->status,['pending','under_review'],true);
  DB::transaction(function()use($withdrawal,$note){$withdrawal=WithdrawalRequest::lockForUpdate()->findOrFail($withdrawal->id);if(!in_array($withdrawal->status,['pending','under_review'],true))return;$wallet=SellerWallet::lockForUpdate()->findOrFail($withdrawal->seller_wallet_id);$wallet->decrement('reserved_balance',(float)$withdrawal->amount);$wallet->increment('available_balance',(float)$withdrawal->amount);$withdrawal->update(['status'=>'rejected','administrator_note'=>$note,'processed_at'=>now()]);$wallet->transactions()->create(['uuid'=>(string)str()->uuid(),'type'=>'withdrawal_reversal','balance_bucket'=>'available','amount'=>$withdrawal->amount,'currency'=>$wallet->currency,'reference'=>'withdrawal:'.$withdrawal->id.':reversal','created_at'=>now()]);});
  if($wasOpen&&$withdrawal->fresh()->status==='rejected')app(MarketplaceMailer::class)->withdrawalDecided($withdrawal->fresh());
 }
}
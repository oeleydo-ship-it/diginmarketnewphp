<?php
namespace App\Services;
use App\Contracts\RefundGateway;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\SellerWallet;
use App\Models\WalletTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class RefundService
{
 public function __construct(private RefundGateway $gateway){}
 public function request(User $user,\App\Models\OrderItem $item,array $data):RefundRequest{if($item->order->user_id!==$user->id||!$item->order->isSettled())throw ValidationException::withMessages(['order'=>'Only paid purchases are eligible.']);if($item->refundRequest()->whereIn('status',['submitted','under_review','refunded'])->exists())throw ValidationException::withMessages(['order'=>'A refund request already exists for this purchase.']);$refund=RefundRequest::create(['number'=>'RF-'.str()->upper(str()->random(10)),'user_id'=>$user->id,'order_id'=>$item->order_id,'order_item_id'=>$item->id,'product_id'=>$item->product_id,'seller_id'=>$item->seller_id,'reason'=>$data['reason'],'description'=>$data['description'],'requested_amount'=>self::refundableAmount($item),'status'=>'submitted']);app(AdminNotifier::class)->notify('refund','Refund requested: '.$refund->number.' for '.$item->product_title,route('admin.refunds.index'));return $refund;}
 /** What the buyer actually paid for this line: the net total PLUS the tax charged on top of it. */
 public static function refundableAmount(\App\Models\OrderItem $item):float{return round((float)$item->total+(float)$item->tax,2);}
 public function approve(RefundRequest $request,User $admin,float $amount,string $decision=''):void
 {
  $amount=round(min($amount,(float)$request->requested_amount),2);
  // Claim the request BEFORE touching the payment provider. Calling the gateway first meant a
  // double-submitted approval refunded the buyer twice at the provider while the ledger only ever
  // recorded one deduction — real money out the door with no record of it.
  $claimedFrom=DB::transaction(function()use($request){$row=RefundRequest::lockForUpdate()->findOrFail($request->id);if(!in_array($row->status,['submitted','under_review'],true))return null;$was=$row->status;$row->update(['status'=>'processing']);return $was;});
  if($claimedFrom===null)return;
  $payment=$request->orderItem->order->payments()->where('status','succeeded')->first();
  try{
   if(!$payment)throw ValidationException::withMessages(['refund'=>'This order has no settled payment to refund.']);
   $provider=$this->gateway->refund($payment,$amount);
   if(($provider['status']??null)!=='succeeded')throw ValidationException::withMessages(['refund'=>'The payment provider did not confirm the refund.']);
  }catch(\Throwable $e){
   // A RuntimeException/ValidationException means the provider answered and refused, so no money
   // moved: release the claim and leave the request exactly as it was for the admin to retry.
   // Anything else (a timeout, a dropped connection) is ambiguous — the refund may well have gone
   // through — so the request stays 'processing' and the admin reconciles it by hand.
   if($e instanceof \RuntimeException||$e instanceof ValidationException)RefundRequest::whereKey($request->id)->where('status','processing')->update(['status'=>$claimedFrom]);
   throw $e;
  }
  DB::transaction(function()use($request,$admin,$amount,$decision,$provider){
   $request=RefundRequest::lockForUpdate()->findOrFail($request->id);
   if($request->status==='refunded')return;
   $item=$request->orderItem;
   $item->license?->update(['status'=>'refunded']);
   $request->update(['approved_amount'=>$amount,'status'=>'refunded','administrator_decision'=>$decision,'decided_at'=>now()]);
   $this->settleOrderState($item->order);
   $wallet=SellerWallet::where('seller_id',$item->seller_id)->where('currency',$item->order->currency)->lockForUpdate()->first();
   if($wallet){
    // Never claw back more than the seller actually earned — the platform's commission and the
    // buyer's tax are not the seller's money to return.
    $deduction=min($amount,(float)$item->seller_earning);
    $bucket=(float)$wallet->pending_balance>=$deduction?'pending':'available';
    $bucket==='pending'?$wallet->decrement('pending_balance',$deduction):$wallet->decrement('available_balance',$deduction);
    $wallet->transactions()->create(['uuid'=>(string)str()->uuid(),'order_item_id'=>$item->id,'type'=>'refund_deduction','balance_bucket'=>$bucket,'amount'=>-$deduction,'currency'=>$wallet->currency,'reference'=>'refund:'.$request->id,'metadata'=>['provider_refund_id'=>$provider['id'],'approved_by'=>$admin->id],'created_at'=>now()]);
   }
   AuditLog::create(['user_id'=>$admin->id,'action'=>'refund.approved','entity_type'=>RefundRequest::class,'entity_id'=>$request->id,'old_values'=>['status'=>'submitted'],'new_values'=>['status'=>'refunded','amount'=>(string)$amount,'provider_refund_id'=>$provider['id']],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  });
  if($request->fresh()->status==='refunded')app(MarketplaceMailer::class)->refundDecided($request->fresh());
 }
 public function reject(RefundRequest $request,User $admin,string $decision=''):void{$wasOpen=in_array($request->status,['submitted','under_review'],true);DB::transaction(function()use($request,$admin,$decision){$request=RefundRequest::lockForUpdate()->findOrFail($request->id);if(!in_array($request->status,['submitted','under_review'],true))return;$request->update(['status'=>'rejected','administrator_decision'=>$decision,'decided_at'=>now()]);AuditLog::create(['user_id'=>$admin->id,'action'=>'refund.rejected','entity_type'=>RefundRequest::class,'entity_id'=>$request->id,'old_values'=>['status'=>'submitted'],'new_values'=>['status'=>'rejected'],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);});if($wasOpen&&$request->fresh()->status==='rejected')app(MarketplaceMailer::class)->refundDecided($request->fresh());}
 /** An order is fully refunded only once every line has been; otherwise it is partially refunded. */
 private function settleOrderState(Order $order):void
 {
  $items=$order->items()->pluck('id');
  $refunded=RefundRequest::whereIn('order_item_id',$items)->where('status','refunded')->distinct()->count('order_item_id');
  $state=$refunded>=$items->count()?'refunded':'partially_refunded';
  $order->update(['status'=>$state,'payment_status'=>$state]);
 }
}

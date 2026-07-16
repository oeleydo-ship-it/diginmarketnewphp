<?php
namespace App\Services;
use App\Models\AuditLog;
use App\Models\Dispute;
use App\Models\OrderItem;
use App\Models\SellerWallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class DisputeService
{
 public function open(User $user,OrderItem $item,array $data):Dispute
 {
  if($item->order->user_id!==$user->id||!in_array($item->order->payment_status,['paid','partially_refunded'],true))throw ValidationException::withMessages(['order'=>'Only paid purchases can be disputed.']);
  if(Dispute::where('order_item_id',$item->id)->exists())throw ValidationException::withMessages(['order'=>'A dispute already exists for this purchase.']);
  $dispute=DB::transaction(function()use($user,$item,$data){
   $dispute=Dispute::create(['number'=>'DP-'.str()->upper(str()->random(10)),'user_id'=>$user->id,'order_id'=>$item->order_id,'order_item_id'=>$item->id,'product_id'=>$item->product_id,'seller_id'=>$item->seller_id,'type'=>$data['type'],'description'=>$data['description'],'disputed_amount'=>$item->total,'status'=>'open']);
   if($item->license&&$item->license->status==='active')$item->license->update(['status'=>'suspended']);
   return $dispute;
  });
  app(AdminNotifier::class)->notify('dispute','Dispute opened: '.$dispute->number.' for '.$item->product_title,route('admin.disputes.index'));
  return $dispute;
 }
 public function uphold(Dispute $dispute,User $admin,float $amount,string $decision=''):void
 {
  DB::transaction(function()use($dispute,$admin,$amount,$decision){
   $dispute=Dispute::lockForUpdate()->findOrFail($dispute->id);
   if(!in_array($dispute->status,['open','under_review'],true))return;
   $amount=min($amount,(float)$dispute->disputed_amount);
   $item=$dispute->orderItem;
   $item->license?->update(['status'=>'revoked']);
   $item->order->update(['status'=>'disputed','payment_status'=>'disputed']);
   $dispute->update(['status'=>'upheld','resolved_amount'=>$amount,'administrator_decision'=>$decision,'resolved_at'=>now()]);
   $wallet=SellerWallet::where('seller_id',$item->seller_id)->where('currency',$item->order->currency)->lockForUpdate()->first();
   if($wallet){
    $deduction=min($amount,(float)$item->seller_earning);
    $bucket=(float)$wallet->pending_balance>=$deduction?'pending':'available';
    $bucket==='pending'?$wallet->decrement('pending_balance',$deduction):$wallet->decrement('available_balance',$deduction);
    $wallet->transactions()->create(['uuid'=>(string)str()->uuid(),'order_item_id'=>$item->id,'type'=>'dispute_deduction','balance_bucket'=>$bucket,'amount'=>-$deduction,'currency'=>$wallet->currency,'reference'=>'dispute:'.$dispute->id,'metadata'=>['upheld_by'=>$admin->id],'created_at'=>now()]);
   }
   AuditLog::create(['user_id'=>$admin->id,'action'=>'dispute.upheld','entity_type'=>Dispute::class,'entity_id'=>$dispute->id,'old_values'=>['status'=>'open'],'new_values'=>['status'=>'upheld','resolved_amount'=>$amount],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  });
 }
 public function dismiss(Dispute $dispute,User $admin,string $decision=''):void
 {
  DB::transaction(function()use($dispute,$admin,$decision){
   $dispute=Dispute::lockForUpdate()->findOrFail($dispute->id);
   if(!in_array($dispute->status,['open','under_review'],true))return;
   $item=$dispute->orderItem;
   if($item->license&&$item->license->status==='suspended')$item->license->update(['status'=>'active']);
   $dispute->update(['status'=>'dismissed','administrator_decision'=>$decision,'resolved_at'=>now()]);
   AuditLog::create(['user_id'=>$admin->id,'action'=>'dispute.dismissed','entity_type'=>Dispute::class,'entity_id'=>$dispute->id,'old_values'=>['status'=>'open'],'new_values'=>['status'=>'dismissed'],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  });
 }
}

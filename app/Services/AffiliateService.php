<?php
namespace App\Services;
use App\Http\Middleware\TrackAffiliateReferral;
use App\Models\AffiliateEarning;
use App\Models\AffiliateProfile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class AffiliateService
{
 public function enroll(User $user):AffiliateProfile
 {
  return AffiliateProfile::firstOrCreate(['user_id'=>$user->id],['code'=>$this->uniqueCode(),'commission_rate'=>(float)config('marketplace.affiliate_commission_rate',20),'status'=>'active']);
 }
 public function resolveForCheckout(User $buyer):?AffiliateProfile
 {
  $code=(string)request()->cookie(TrackAffiliateReferral::COOKIE,'');
  if($code==='')return null;
  $profile=AffiliateProfile::where('code',$code)->where('status','active')->first();
  return $profile&&$profile->user_id!==$buyer->id?$profile:null;
 }
 public function creditReferral(Order $order):?AffiliateEarning
 {
  if(!$order->affiliate_profile_id)return null;
  return DB::transaction(function()use($order){
   if($existing=AffiliateEarning::where('order_id',$order->id)->first())return $existing;
   $profile=AffiliateProfile::lockForUpdate()->find($order->affiliate_profile_id);
   if(!$profile||$profile->status!=='active')return null;
   $commissionPool=(float)$order->items()->sum('platform_commission');
   $amount=round($commissionPool*((float)$profile->commission_rate/100),2);
   if($amount<=0)return null;
   $earning=AffiliateEarning::create(['affiliate_profile_id'=>$profile->id,'order_id'=>$order->id,'amount'=>$amount,'currency'=>$order->currency,'status'=>'pending']);
   $profile->increment('referred_orders');
   $profile->increment('total_earnings',$amount);
   return $earning;
  });
 }
 private function uniqueCode():string
 {
  do{$code=str()->upper(str()->random(8));}while(AffiliateProfile::where('code',$code)->exists());
  return $code;
 }
}

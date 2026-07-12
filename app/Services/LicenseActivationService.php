<?php
namespace App\Services;
use App\Models\License;
use App\Models\LicenseActivation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class LicenseActivationService
{
 public function findUsableLicense(string $key):License
 {
  $license=License::with(['product','orderItem.order'])->where('license_key',$key)->first();
  if(!$license||$license->status!=='active'||$license->orderItem?->order?->payment_status!=='paid')throw ValidationException::withMessages(['license_key'=>'License key is not valid or not active.']);
  return $license;
 }
 public function activate(License $license,string $instanceId,?string $label,?string $ip,?string $userAgent):LicenseActivation
 {
  return DB::transaction(function()use($license,$instanceId,$label,$ip,$userAgent){
   $license=License::lockForUpdate()->findOrFail($license->id);
   $existing=LicenseActivation::where('license_id',$license->id)->where('instance_id',$instanceId)->first();
   if($existing&&$existing->status==='active')return $existing;
   $active=LicenseActivation::where('license_id',$license->id)->where('status','active')->count();
   if($license->activation_limit&&$active>=$license->activation_limit)throw ValidationException::withMessages(['instance_id'=>'Activation limit reached. Deactivate another installation first.']);
   if($existing){$existing->update(['status'=>'active','label'=>$label??$existing->label,'ip_address'=>$ip,'user_agent'=>$userAgent,'activated_at'=>now(),'deactivated_at'=>null]);}
   else{$existing=LicenseActivation::create(['license_id'=>$license->id,'instance_id'=>$instanceId,'label'=>$label,'ip_address'=>$ip,'user_agent'=>$userAgent,'status'=>'active','activated_at'=>now()]);}
   $license->update(['activation_count'=>LicenseActivation::where('license_id',$license->id)->where('status','active')->count()]);
   return $existing;
  });
 }
 public function deactivate(License $license,string $instanceId):void
 {
  DB::transaction(function()use($license,$instanceId){
   $license=License::lockForUpdate()->findOrFail($license->id);
   $activation=LicenseActivation::where('license_id',$license->id)->where('instance_id',$instanceId)->where('status','active')->first();
   if(!$activation)throw ValidationException::withMessages(['instance_id'=>'No active installation found for this instance.']);
   $activation->update(['status'=>'deactivated','deactivated_at'=>now()]);
   $license->update(['activation_count'=>LicenseActivation::where('license_id',$license->id)->where('status','active')->count()]);
  });
 }
 public function updateEligibility(License $license):array
 {
  $latest=$license->product->versions()->where('status','published')->orderByDesc('published_at')->first();
  $current=$license->version;
  $supportActive=!$license->support_expires_at||$license->support_expires_at->isFuture();
  return [
   'current_version'=>$current?->version_number,
   'latest_version'=>$latest?->version_number,
   'update_available'=>(bool)($latest&&$current&&$latest->id!==$current->id),
   'support_active'=>$supportActive,
   'support_expires_at'=>$license->support_expires_at?->toIso8601String(),
   'eligible'=>(bool)($latest&&$supportActive),
  ];
 }
}

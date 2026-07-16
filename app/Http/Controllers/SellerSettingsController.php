<?php
namespace App\Http\Controllers;
use App\Models\AuditLog;
use App\Models\SellerProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class SellerSettingsController extends Controller
{
 public function edit(): View
 {
  $profile=auth()->user()->sellerProfile()->firstOrFail();
  return view('seller.settings',compact('profile'));
 }
 public function update(): RedirectResponse
 {
  $profile=auth()->user()->sellerProfile()->firstOrFail();
  $data=request()->validate([
   'display_name'=>['required','string','max:100'],
   'business_name'=>['nullable','string','max:150'],
   'website'=>['nullable','url:http,https','max:255'],
   'phone'=>['nullable','string','max:30'],
   'biography'=>['required','string','min:20','max:2000'],
   'address'=>['nullable','string','max:255'],
   'city'=>['nullable','string','max:100'],
   'postal_code'=>['nullable','string','max:20'],
  ]);
  $old=collect($profile->only(array_keys($data)))->filter(fn($value,$key)=>(string)$value!==(string)($data[$key]??''))->all();
  $profile->update($data);
  if($old!==[])AuditLog::create(['user_id'=>auth()->id(),'action'=>'seller.settings_updated','entity_type'=>SellerProfile::class,'entity_id'=>$profile->id,'old_values'=>$old,'new_values'=>collect($data)->only(array_keys($old))->all(),'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status','Seller settings saved.');
 }
}

<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Coupon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
class CouponController extends Controller
{
 public function index(): View
 {
  $coupons=Coupon::withCount('usages')->with('seller')->when(request('q'),fn($query,$term)=>$query->where('code','like','%'.$term.'%'))->latest()->paginate(25)->withQueryString();
  return view('admin.coupons.index',compact('coupons'));
 }
 public function store(): RedirectResponse
 {
  $data=$this->validated();
  $coupon=Coupon::create($data);
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'coupon.created','entity_type'=>Coupon::class,'entity_id'=>$coupon->id,'new_values'=>['code'=>$coupon->code,'type'=>$coupon->type,'value'=>(string)$coupon->value],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status','Coupon '.$coupon->code.' created.');
 }
 public function toggle(Coupon $coupon): RedirectResponse
 {
  $coupon->update(['is_active'=>!$coupon->is_active]);
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'coupon.toggled','entity_type'=>Coupon::class,'entity_id'=>$coupon->id,'new_values'=>['is_active'=>$coupon->is_active],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status','Coupon '.$coupon->code.' '.($coupon->is_active?'activated':'deactivated').'.');
 }
 public function destroy(Coupon $coupon): RedirectResponse
 {
  abort_if($coupon->usages()->exists(),422,'Coupons that have been redeemed cannot be deleted; deactivate instead.');
  $coupon->delete();
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'coupon.deleted','entity_type'=>Coupon::class,'entity_id'=>$coupon->id,'old_values'=>['code'=>$coupon->code],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status','Coupon deleted.');
 }
 private function validated(): array
 {
  $data=request()->validate(['code'=>['required','string','max:40','alpha_dash',Rule::unique('coupons','code')],'description'=>['nullable','string','max:255'],'type'=>['required','in:percent,fixed'],'value'=>['required','numeric','min:0.01','max:100000'],'min_cart_total'=>['nullable','numeric','min:0'],'max_uses'=>['nullable','integer','min:1'],'max_uses_per_user'=>['nullable','integer','min:1'],'starts_at'=>['nullable','date'],'ends_at'=>['nullable','date','after:starts_at']]);
  if($data['type']==='percent'&&(float)$data['value']>100)throw \Illuminate\Validation\ValidationException::withMessages(['value'=>'Percentage discounts cannot exceed 100.']);
  $data['code']=strtoupper($data['code']);
  $data['max_uses_per_user']=$data['max_uses_per_user']??1;
  return $data;
 }
}

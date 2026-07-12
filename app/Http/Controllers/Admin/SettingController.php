<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class SettingController extends Controller
{
 public function index(): View {$settings=Setting::query()->when(request('q'),fn($query,$term)=>$query->where(fn($query)=>$query->where('key','like','%'.$term.'%')->orWhere('group','like','%'.$term.'%')))->orderBy('group')->orderBy('key')->get()->groupBy('group');return view('admin.settings.index',compact('settings'));}
 public function update(): RedirectResponse
 {
  $data=request()->validate(['group'=>['required','string','max:64'],'key'=>['required','string','max:128'],'value'=>['nullable','string','max:5000']]);
  $setting=Setting::firstOrNew(['key'=>$data['key']]);
  $old=$setting->exists?$setting->value:null;
  $setting->fill(['group'=>$data['group'],'value'=>$data['value']??''])->save();
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'setting.updated','entity_type'=>Setting::class,'entity_id'=>$setting->id,'old_values'=>['value'=>$old],'new_values'=>['value'=>$setting->value],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status','Setting saved.');
 }
}

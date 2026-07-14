<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MenuItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class MenuItemController extends Controller
{
 public function index(): View {$items=MenuItem::orderBy('location')->orderBy('display_order')->get()->groupBy('location');return view('admin.menus.index',compact('items'));}
 public function store(): RedirectResponse
 {
  $data=$this->validated();
  $item=MenuItem::create($data);
  MenuItem::bustCache();
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'menu_item.created','entity_type'=>MenuItem::class,'entity_id'=>$item->id,'new_values'=>$data,'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status','Menu item added.');
 }
 public function update(MenuItem $menuItem): RedirectResponse
 {
  $data=$this->validated();
  $old=$menuItem->only(array_keys($data));
  $menuItem->update($data);
  MenuItem::bustCache();
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'menu_item.updated','entity_type'=>MenuItem::class,'entity_id'=>$menuItem->id,'old_values'=>$old,'new_values'=>$data,'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status','Menu item updated.');
 }
 public function destroy(MenuItem $menuItem): RedirectResponse
 {
  $menuItem->delete();
  MenuItem::bustCache();
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'menu_item.deleted','entity_type'=>MenuItem::class,'entity_id'=>$menuItem->id,'old_values'=>['label'=>$menuItem->label,'url'=>$menuItem->url],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status','Menu item removed.');
 }
 private function validated(): array
 {
  return request()->validate(['location'=>['required','in:footer-legal,footer-resources'],'label'=>['required','string','max:100'],'url'=>['required','string','max:500'],'display_order'=>['nullable','integer','min:0'],'is_active'=>['nullable','boolean']])+['display_order'=>(int)request('display_order',0),'is_active'=>request()->boolean('is_active',true)];
 }
}

<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
class CategoryController extends Controller
{
 public function index(): View
 {
  $categories=Category::withCount('products')->orderBy('display_order')->orderBy('name')->get();
  return view('admin.categories.index',compact('categories'));
 }
 public function store(): RedirectResponse
 {
  $data=$this->validated();
  $data['slug']=$this->uniqueSlug($data['name']);
  if(request()->hasFile('image'))$data['image_path']=request()->file('image')->store('categories','public');
  $category=Category::create($data);
  Cache::forget(\App\Http\Controllers\HomeController::CACHE_KEY);
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'category.created','entity_type'=>Category::class,'entity_id'=>$category->id,'new_values'=>['name'=>$category->name,'slug'=>$category->slug],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status','Category "'.$category->name.'" created.');
 }
 public function update(Category $category): RedirectResponse
 {
  $data=$this->validated($category);
  if(request()->hasFile('image')){
   $old=$category->image_path;
   $data['image_path']=request()->file('image')->store('categories','public');
   if($old)Storage::disk('public')->delete($old);
  }
  $category->update($data);
  Cache::forget(\App\Http\Controllers\HomeController::CACHE_KEY);
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'category.updated','entity_type'=>Category::class,'entity_id'=>$category->id,'new_values'=>['name'=>$category->name],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status','Category "'.$category->name.'" updated.');
 }
 public function removeImage(Category $category): RedirectResponse
 {
  if($category->image_path){Storage::disk('public')->delete($category->image_path);$category->update(['image_path'=>null]);Cache::forget(\App\Http\Controllers\HomeController::CACHE_KEY);}
  return back()->with('status','Category image removed.');
 }
 public function toggle(Category $category): RedirectResponse
 {
  $category->update(['is_active'=>!$category->is_active]);
  Cache::forget(\App\Http\Controllers\HomeController::CACHE_KEY);
  return back()->with('status','Category "'.$category->name.'" '.($category->is_active?'activated':'hidden').'.');
 }
 public function destroy(Category $category): RedirectResponse
 {
  abort_if($category->products()->exists(),422,'Categories with products cannot be deleted; hide it instead.');
  if($category->image_path)Storage::disk('public')->delete($category->image_path);
  $name=$category->name;
  $category->delete();
  Cache::forget(\App\Http\Controllers\HomeController::CACHE_KEY);
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'category.deleted','entity_type'=>Category::class,'entity_id'=>$category->id,'old_values'=>['name'=>$name],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return back()->with('status','Category deleted.');
 }
 private function validated(?Category $category=null): array
 {
  $data=request()->validate([
   'name'=>['required','string','max:120'],
   'description'=>['nullable','string','max:500'],
   'icon'=>['nullable','string','max:60'],
   'image'=>['nullable','image','mimes:jpg,jpeg,png,webp,svg','max:2048'],
   'display_order'=>['nullable','integer','min:0','max:9999'],
   'commission_rate'=>['nullable','numeric','min:0','max:100'],
  ]);
  unset($data['image']);
  $data['display_order']=$data['display_order']??0;
  $data['is_active']=request()->boolean('is_active');
  return $data;
 }
 private function uniqueSlug(string $name): string
 {
  $base=str($name)->slug()->value()?:'category';
  $slug=$base;$i=2;
  while(Category::where('slug',$slug)->exists())$slug=$base.'-'.$i++;
  return $slug;
 }
}

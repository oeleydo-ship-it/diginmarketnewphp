<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
class PageController extends Controller
{
 public function index(): View {$pages=Page::latest()->paginate(25);return view('admin.pages.index',compact('pages'));}
 public function create(): View {return view('admin.pages.form',['page'=>new Page()]);}
 public function store(): RedirectResponse
 {
  $data=$this->validated();
  $page=Page::create([...$data,'created_by'=>auth()->id(),'published_at'=>$data['status']==='published'?now():null]);
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'page.created','entity_type'=>Page::class,'entity_id'=>$page->id,'new_values'=>['slug'=>$page->slug,'status'=>$page->status],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return redirect()->route('admin.pages.index')->with('status','Page created.');
 }
 public function edit(Page $page): View {return view('admin.pages.form',compact('page'));}
 public function update(Page $page): RedirectResponse
 {
  $data=$this->validated($page);
  $old=['status'=>$page->status,'slug'=>$page->slug];
  $page->update([...$data,'published_at'=>$data['status']==='published'?($page->published_at??now()):null]);
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'page.updated','entity_type'=>Page::class,'entity_id'=>$page->id,'old_values'=>$old,'new_values'=>['slug'=>$page->slug,'status'=>$page->status],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return redirect()->route('admin.pages.index')->with('status','Page updated.');
 }
 private function validated(?Page $page=null): array
 {
  return request()->validate(['title'=>['required','string','max:200'],'slug'=>['required','string','max:200','alpha_dash',Rule::unique('pages','slug')->ignore($page?->id)],'excerpt'=>['nullable','string','max:500'],'body'=>['required','string','max:100000'],'meta_title'=>['nullable','string','max:200'],'meta_description'=>['nullable','string','max:500'],'status'=>['required','in:draft,published']]);
 }
}

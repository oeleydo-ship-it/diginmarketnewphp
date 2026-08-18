<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Models\AuditLog;
use App\Models\Page;
use App\Support\RichText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
class PageController extends Controller
{
 public function index(): View
 {
  $pages=Page::query()->where('slug','!=',Page::HOMEPAGE_SLUG)->when(request('q'),fn($query,$term)=>$query->where(fn($query)=>$query->where('title','like','%'.$term.'%')->orWhere('slug','like','%'.$term.'%')))->when(request('status'),fn($query,$status)=>$query->where('status',$status))->latest()->paginate(25)->withQueryString();
  $homepage=Page::firstOrNew(['slug'=>Page::HOMEPAGE_SLUG],Page::homepageDefaults());
  $suggestedPages=Page::query()->whereIn('slug',['privacy-policy','terms-of-service','seller-agreement'])->get()->keyBy('slug');
  return view('admin.pages.index',compact('pages','homepage','suggestedPages'));
 }
 public function create(): View {return view('admin.pages.form',['page'=>new Page()]);}
 public function store(): RedirectResponse
 {
  $data=$this->validated();
  $page=Page::create([...$data,'created_by'=>auth()->id(),'published_at'=>$data['status']==='published'?now():null]);
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'page.created','entity_type'=>Page::class,'entity_id'=>$page->id,'new_values'=>['slug'=>$page->slug,'status'=>$page->status],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return redirect()->route('admin.pages.index')->with('status','Page created.');
 }
 public function edit(Page $page): View {return view('admin.pages.form',compact('page'));}
 public function editHomepage(): View
 {
  $page=Page::firstOrNew(['slug'=>Page::HOMEPAGE_SLUG],Page::homepageDefaults());
  return view('admin.pages.form',['page'=>$page,'isHomepageEditor'=>true,'homepageSettings'=>$page->homepageSettings()]);
 }
 public function update(Page $page): RedirectResponse
 {
  $data=$this->validated($page);
  $old=['status'=>$page->status,'slug'=>$page->slug];
  $page->update([...$data,'published_at'=>$data['status']==='published'?($page->published_at??now()):null]);
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'page.updated','entity_type'=>Page::class,'entity_id'=>$page->id,'old_values'=>$old,'new_values'=>['slug'=>$page->slug,'status'=>$page->status],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return redirect()->route('admin.pages.index')->with('status','Page updated.');
 }
 public function updateHomepage(): RedirectResponse
 {
  $page=Page::firstOrNew(['slug'=>Page::HOMEPAGE_SLUG],[...Page::homepageDefaults(),'created_by'=>auth()->id()]);
  $data=$this->validatedHomepage();
  $old=$page->exists ? ['status'=>$page->status,'slug'=>$page->slug] : null;
  $page->fill([...$data,'slug'=>Page::HOMEPAGE_SLUG,'created_by'=>$page->created_by ?: auth()->id(),'published_at'=>$data['status']==='published'?($page->published_at??now()):null]);
  $page->save();
  AuditLog::create(['user_id'=>auth()->id(),'action'=>$old ? 'page.updated' : 'page.created','entity_type'=>Page::class,'entity_id'=>$page->id,'old_values'=>$old,'new_values'=>['slug'=>$page->slug,'status'=>$page->status],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  cache()->forget(HomeController::CACHE_KEY);
  return redirect()->route('admin.pages.index')->with('status','Homepage updated.');
 }
 private function validated(?Page $page=null): array
 {
  $data=request()->validate(['title'=>['required','string','max:200'],'slug'=>['required','string','max:200','alpha_dash','not_in:'.Page::HOMEPAGE_SLUG,Rule::unique('pages','slug')->ignore($page?->id)],'excerpt'=>['nullable','string','max:500'],'body'=>['required','string','max:100000'],'meta_title'=>['nullable','string','max:200'],'meta_description'=>['nullable','string','max:500'],'status'=>['required','in:draft,published']]);
  $data['body']=RichText::sanitize($data['body']);
  return $data;
 }
 private function validatedHomepage(): array
 {
  $data=request()->validate([
   'title'=>['required','string','max:200'],
   'excerpt'=>['nullable','string','max:500'],
   'body'=>['nullable','string','max:100000'],
   'meta_title'=>['nullable','string','max:200'],
   'meta_description'=>['nullable','string','max:500'],
   'status'=>['required','in:draft,published'],
   'settings.hero_badge_text'=>['nullable','string','max:120'],
   'settings.cta_primary_text'=>['nullable','string','max:80'],
   'settings.cta_primary_url'=>['nullable','string','max:500'],
   'settings.cta_secondary_text'=>['nullable','string','max:80'],
   'settings.cta_secondary_url'=>['nullable','string','max:500'],
   'settings.show_categories'=>['nullable','boolean'],
   'settings.categories_title'=>['nullable','string','max:120'],
   'settings.categories_subtitle'=>['nullable','string','max:255'],
   'settings.show_trending'=>['nullable','boolean'],
   'settings.trending_title'=>['nullable','string','max:120'],
   'settings.trending_subtitle'=>['nullable','string','max:255'],
   'settings.show_new_arrivals'=>['nullable','boolean'],
   'settings.new_arrivals_title'=>['nullable','string','max:120'],
   'settings.new_arrivals_subtitle'=>['nullable','string','max:255'],
   'settings.show_featured_creators'=>['nullable','boolean'],
   'settings.featured_creators_title'=>['nullable','string','max:120'],
   'settings.featured_creators_subtitle'=>['nullable','string','max:255'],
  ]);
  $settings=array_replace(Page::homepageSettingsDefaults(),$data['settings'] ?? []);
  foreach(['show_categories','show_trending','show_new_arrivals','show_featured_creators'] as $key)$settings[$key]=filter_var($settings[$key] ?? false,FILTER_VALIDATE_BOOL);
  $data['settings']=$settings;
  $data['body']=RichText::sanitize((string) ($data['body'] ?? ''));
  return $data;
 }
}

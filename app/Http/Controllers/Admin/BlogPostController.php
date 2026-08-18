<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BlogPost;
use App\Support\RichText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
class BlogPostController extends Controller
{
 public function index(): View
 {
  $posts=BlogPost::with('author')->when(request('q'),fn($query,$term)=>$query->where(fn($q)=>$q->where('title','like','%'.$term.'%')->orWhere('slug','like','%'.$term.'%')))->when(request('status'),fn($query,$status)=>$query->where('status',$status))->latest()->paginate(25)->withQueryString();
  return view('admin.blog.index',compact('posts'));
 }
 public function create(): View {return view('admin.blog.form',['post'=>new BlogPost()]);}
 public function store(): RedirectResponse
 {
  $data=$this->validated();
  $post=BlogPost::create([...$data,'author_id'=>auth()->id(),'published_at'=>$data['status']==='published'?now():null]);
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'blog_post.created','entity_type'=>BlogPost::class,'entity_id'=>$post->id,'new_values'=>['slug'=>$post->slug,'status'=>$post->status],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return redirect()->route('admin.blog.index')->with('status','Post created.');
 }
 public function edit(BlogPost $post): View {return view('admin.blog.form',compact('post'));}
 public function update(BlogPost $post): RedirectResponse
 {
  $data=$this->validated($post);
  $old=['status'=>$post->status,'slug'=>$post->slug];
  $post->update([...$data,'published_at'=>$data['status']==='published'?($post->published_at??now()):null]);
  AuditLog::create(['user_id'=>auth()->id(),'action'=>'blog_post.updated','entity_type'=>BlogPost::class,'entity_id'=>$post->id,'old_values'=>$old,'new_values'=>['slug'=>$post->slug,'status'=>$post->status],'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]);
  return redirect()->route('admin.blog.index')->with('status','Post updated.');
 }
 private function validated(?BlogPost $post=null): array
 {
  $data=request()->validate(['title'=>['required','string','max:200'],'slug'=>['required','string','max:200','alpha_dash',Rule::unique('blog_posts','slug')->ignore($post?->id)],'excerpt'=>['nullable','string','max:500'],'body'=>['required','string','max:100000'],'meta_title'=>['nullable','string','max:200'],'meta_description'=>['nullable','string','max:500'],'status'=>['required','in:draft,published']]);
  $data['body']=RichText::sanitize($data['body']);
  return $data;
 }
}

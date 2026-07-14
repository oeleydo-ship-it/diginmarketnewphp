<?php
namespace App\Http\Controllers;
use App\Models\BlogPost;
use Illuminate\Contracts\View\View;
class BlogController extends Controller
{
 public function index(): View {abort_unless(\App\Models\Setting::enabled('features.blog'),404);$posts=BlogPost::published()->latest('published_at')->paginate(12);return view('blog.index',compact('posts'));}
 public function show(string $slug): View {abort_unless(\App\Models\Setting::enabled('features.blog'),404);$post=BlogPost::published()->where('slug',$slug)->firstOrFail();$more=BlogPost::published()->where('id','!=',$post->id)->latest('published_at')->limit(3)->get();return view('blog.show',compact('post','more'));}
}

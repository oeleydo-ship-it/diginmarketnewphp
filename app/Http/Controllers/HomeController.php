<?php
namespace App\Http\Controllers;
use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SellerProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
class HomeController extends Controller
{
 public const CACHE_KEY='homepage:sections';
 public function __invoke(): View
 {
  $sections=Cache::remember(self::CACHE_KEY,600,fn()=>[
   'homepagePage'=>Page::published()->where('slug',Page::HOMEPAGE_SLUG)->first(),
   'categories'=>Category::where('is_active',true)->withCount(['products'=>fn($q)=>$q->published()])->orderBy('display_order')->orderBy('name')->limit(18)->get(),
   'featured'=>Product::published()->with(['category','seller.sellerProfile'])->where('is_featured',true)->latest('published_at')->limit(6)->get(),
   'newest'=>Product::published()->with(['category','seller.sellerProfile'])->latest('published_at')->limit(8)->get(),
   'trending'=>Product::published()->with(['category','seller.sellerProfile'])->where('is_trending',true)->orderByDesc('sales_count')->limit(8)->get(),
   'bestSellers'=>Product::published()->with(['category','seller.sellerProfile'])->orderByDesc('sales_count')->limit(8)->get(),
   'authors'=>SellerProfile::query()->select('seller_profiles.*')->where('status',SellerStatus::Approved)
    ->whereHas('user.products',fn($q)=>$q->published())
    ->with('user')
    ->addSelect(['published_products_count'=>Product::published()->selectRaw('count(*)')->whereColumn('seller_id','seller_profiles.user_id')])
    ->orderByDesc('is_featured')
    ->orderByDesc('published_products_count')
    ->limit(8)
    ->get(),
   'stats'=>[
    'products'=>Product::published()->count(),
    'creators'=>SellerProfile::where('status',SellerStatus::Approved)->count(),
    'sales'=>(int) Product::published()->sum('sales_count'),
   ],
  ]);
  $homepagePage=$sections['homepagePage'];
  $homepageDefaults=Page::homepageDefaults();
  $homepage=[
   'title'=>$homepagePage?->title ?: $homepageDefaults['title'],
   'excerpt'=>$homepagePage?->excerpt ?: $homepageDefaults['excerpt'],
   'body'=>$homepagePage?->body ?: $homepageDefaults['body'],
   'meta_title'=>$homepagePage?->meta_title ?: $homepageDefaults['meta_title'],
   'meta_description'=>$homepagePage?->meta_description ?: $homepageDefaults['meta_description'],
   'settings'=>$homepagePage?->homepageSettings() ?: $homepageDefaults['settings'],
  ];
  unset($sections['homepagePage']);
  $recentIds=collect(session('recently_viewed',[]))->map(fn($id)=>(int)$id)->filter()->take(8);
  $recentlyViewed=$recentIds->isEmpty()
   ? collect()
   : Product::published()->with(['category','seller.sellerProfile'])->whereIn('id',$recentIds)->get()
    ->sortBy(fn(Product $product)=>$recentIds->search($product->id))->values();
  return view('welcome',[...$sections,'homepage'=>$homepage,'recentlyViewed'=>$recentlyViewed]);
 }
}

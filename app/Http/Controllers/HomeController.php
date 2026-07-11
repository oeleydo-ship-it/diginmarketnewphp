<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
class HomeController extends Controller
{
 public const CACHE_KEY='homepage:sections';
 public function __invoke(): View
 {
  $sections=Cache::remember(self::CACHE_KEY,600,fn()=>[
   'categories'=>Category::where('is_active',true)->withCount(['products'=>fn($q)=>$q->published()])->orderBy('display_order')->limit(8)->get(),
   'featured'=>Product::published()->with(['category','seller.sellerProfile'])->where('is_featured',true)->latest('published_at')->limit(6)->get(),
   'newest'=>Product::published()->with(['category','seller.sellerProfile'])->latest('published_at')->limit(6)->get(),
   'trending'=>Product::published()->with(['category','seller.sellerProfile'])->where('is_trending',true)->orderByDesc('sales_count')->limit(6)->get(),
   'bestSellers'=>Product::published()->with(['category','seller.sellerProfile'])->orderByDesc('sales_count')->limit(6)->get(),
  ]);
  return view('welcome',$sections);
 }
}

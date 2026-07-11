<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
class HomeController extends Controller
{
 public function __invoke(): View
 {
  $categories=Category::where('is_active',true)->withCount(['products'=>fn($q)=>$q->published()])->orderBy('display_order')->limit(8)->get();
  $featured=Product::published()->with(['category','seller.sellerProfile'])->where('is_featured',true)->latest('published_at')->limit(6)->get();
  $newest=Product::published()->with(['category','seller.sellerProfile'])->latest('published_at')->limit(6)->get();
  $trending=Product::published()->with(['category','seller.sellerProfile'])->where('is_trending',true)->orderByDesc('sales_count')->limit(6)->get();
  $bestSellers=Product::published()->with(['category','seller.sellerProfile'])->orderByDesc('sales_count')->limit(6)->get();
  return view('welcome',compact('categories','featured','newest','trending','bestSellers'));
 }
}
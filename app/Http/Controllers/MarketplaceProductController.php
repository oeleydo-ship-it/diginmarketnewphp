<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Http\Requests\ProductSearchRequest;
use App\Services\ProductSearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
class MarketplaceProductController extends Controller
{
 public function index(ProductSearchRequest $request, ProductSearchService $search): View { $products=$search->search($request->validated());$categories=\App\Models\Category::where('is_active',true)->withCount(['products'=>fn($q)=>$q->published()])->orderBy('display_order')->orderBy('name')->get();return view('products.index',compact('products','categories')); }
 public function suggest(ProductSearchService $search): JsonResponse
 {
  $products = $search->suggest((string) request('q', ''));

  return response()->json(['products' => $products]);
 }
 public function show(string $slug): View { $product=Product::published()->with(['seller.sellerProfile','category','versions','images','reviews'=>fn($q)=>$q->where('status','approved')->with('user')->latest(),'comments'=>fn($q)=>$q->whereNull('parent_id')->where('status','approved')->with('user','replies.user')->latest()])->where('slug',$slug)->firstOrFail();$licenseTypes=\App\Models\LicenseType::where('is_active',true)->get();$related=Product::published()->with(['category','seller.sellerProfile'])->where('category_id',$product->category_id)->whereKeyNot($product->id)->orderByDesc('sales_count')->limit(4)->get();$key='viewed_product_'.$product->id;if(!request()->session()->has($key)){$product->increment('views_count');request()->session()->put($key,true);}
  $recent = collect(session('recently_viewed', []))->reject(fn ($id) => (int) $id === $product->id)->prepend($product->id)->take(12)->values();
  session(['recently_viewed' => $recent->all()]);
  // Bundles this product ships in — a cross-sell block on the purchase sidebar.
  $bundles=$product->bundles()->purchasable()->with('products')->withCount('products')->limit(3)->get();
  $ownedLicense=auth()->check()?auth()->user()->licenses()->where('product_id',$product->id)->where('status','active')->latest('id')->first():null;
  $alreadyOwned=$ownedLicense!==null;
  return view('products.show',compact('product','licenseTypes','related','bundles','alreadyOwned','ownedLicense')); }
}

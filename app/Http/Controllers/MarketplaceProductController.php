<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Http\Requests\ProductSearchRequest;
use App\Services\ProductSearchService;
use Illuminate\Contracts\View\View;
class MarketplaceProductController extends Controller
{
 public function index(ProductSearchRequest $request, ProductSearchService $search): View { $products=$search->search($request->validated());$categories=\App\Models\Category::where('is_active',true)->withCount(['products'=>fn($q)=>$q->published()])->orderBy('display_order')->orderBy('name')->get();return view('products.index',compact('products','categories')); }
 public function show(string $slug): View { $product=Product::published()->with(['seller.sellerProfile','category','versions','images','reviews'=>fn($q)=>$q->where('status','approved')->with('user')->latest(),'comments'=>fn($q)=>$q->whereNull('parent_id')->where('status','approved')->with('user','replies.user')->latest()])->where('slug',$slug)->firstOrFail();$licenseTypes=\App\Models\LicenseType::where('is_active',true)->get();$related=Product::published()->with(['category','seller.sellerProfile'])->where('category_id',$product->category_id)->whereKeyNot($product->id)->orderByDesc('sales_count')->limit(4)->get();$key='viewed_product_'.$product->id;if(!request()->session()->has($key)){$product->increment('views_count');request()->session()->put($key,true);}return view('products.show',compact('product','licenseTypes','related')); }
}

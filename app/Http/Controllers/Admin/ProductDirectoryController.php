<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
class ProductDirectoryController extends Controller
{
 public function index(): View
 {
  $products=Product::with(['seller.sellerProfile','category'])
   ->when(request('q'),fn(Builder $q,$term)=>$q->where('title','like','%'.$term.'%'))
   ->when(request('status'),fn(Builder $q,$s)=>$q->where('status',$s))
   ->when(request('category'),fn(Builder $q,$slug)=>$q->whereHas('category',fn(Builder $c)=>$c->where('slug',$slug)))
   ->when(request('sort')==='sales',fn(Builder $q)=>$q->orderByDesc('sales_count'),fn(Builder $q)=>$q->latest())
   ->paginate(20)->withQueryString();
  $metrics=[
   'total'=>Product::count(),
   'published'=>Product::where('status','published')->count(),
   'pending'=>Product::whereIn('status',['submitted','under_review'])->count(),
   'draft'=>Product::whereIn('status',['draft','changes_requested','rejected'])->count(),
  ];
  $categories=Category::orderBy('name')->get(['slug','name']);
  $statuses=['draft','submitted','under_review','changes_requested','approved','published','rejected','paused','suspended','archived'];
  return view('admin.products.directory',compact('products','metrics','categories','statuses'));
 }
}

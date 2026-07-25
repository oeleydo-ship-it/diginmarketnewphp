<?php
namespace App\Http\Controllers;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
class SellerSalesController extends Controller
{
 public function __invoke(): View
 {
  $sellerId=auth()->id();
  $items=OrderItem::where('seller_id',$sellerId)
   ->whereHas('order',fn(Builder $q)=>$q->settled())
   ->when(request('product'),fn(Builder $q,$id)=>$q->where('product_id',$id))
   ->when(request('q'),fn(Builder $q,$term)=>$q->whereHas('order',fn(Builder $o)=>$o->where('number','like','%'.$term.'%')->orWhereHas('user',fn(Builder $u)=>$u->where('name','like','%'.$term.'%')->orWhere('email','like','%'.$term.'%'))))
   ->with(['order.user','product'])->latest('id')->paginate(20)->withQueryString();
  $totals=[
   'count'=>OrderItem::where('seller_id',$sellerId)->whereHas('order',fn(Builder $q)=>$q->settled())->count(),
   'earnings'=>(float)OrderItem::where('seller_id',$sellerId)->whereHas('order',fn(Builder $q)=>$q->settled())->sum('seller_earning'),
   'gross'=>(float)OrderItem::where('seller_id',$sellerId)->whereHas('order',fn(Builder $q)=>$q->settled())->sum('total'),
  ];
  $products=Product::where('seller_id',$sellerId)->orderBy('title')->get(['id','title']);
  return view('seller.sales',compact('items','totals','products'));
 }
}

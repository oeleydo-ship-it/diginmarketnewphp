<?php
namespace App\Http\Controllers;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Contracts\View\View;
class SellerDashboardController extends Controller
{
 public function __invoke(): View
 {
  $sellerId=auth()->id();
  $wallet=auth()->user()->sellerWallets()->where('currency','USD')->firstOrCreate(['currency'=>'USD']);
  $paidItems=OrderItem::where('seller_id',$sellerId)->whereHas('order',fn($q)=>$q->where('payment_status','paid'));
  $stats=[
   'available'=>(float)$wallet->available_balance,
   'pending'=>(float)$wallet->pending_balance,
   'lifetime'=>(float)$wallet->lifetime_earnings,
   'sales'=>(clone $paidItems)->count(),
   'revenue'=>(float)(clone $paidItems)->sum('total'),
   'customers'=>(clone $paidItems)->distinct('order_id')->count('order_id'),
   'products_total'=>Product::where('seller_id',$sellerId)->count(),
   'products_published'=>Product::where('seller_id',$sellerId)->where('status','published')->count(),
   'products_pending'=>Product::where('seller_id',$sellerId)->whereIn('status',['submitted','under_review'])->count(),
  ];
  $recentSales=OrderItem::where('seller_id',$sellerId)->whereHas('order',fn($q)=>$q->where('payment_status','paid'))->with(['order.user','product'])->latest('id')->limit(8)->get();
  $topProducts=Product::where('seller_id',$sellerId)->where('status','published')->orderByDesc('sales_count')->limit(5)->get();
  return view('seller.dashboard',compact('stats','recentSales','topProducts','wallet'));
 }
}

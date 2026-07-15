<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
class SellerCustomerController extends Controller
{
 public function __invoke(): View
 {
  $sellerId=auth()->id();
  // Aggregate paid purchases of this seller's products, grouped by buyer.
  $rows=DB::table('order_items')
   ->join('orders','orders.id','=','order_items.order_id')
   ->join('users','users.id','=','orders.user_id')
   ->where('order_items.seller_id',$sellerId)
   ->where('orders.payment_status','paid')
   ->when(request('q'),fn($q,$term)=>$q->where(fn($w)=>$w->where('users.name','like','%'.$term.'%')->orWhere('users.email','like','%'.$term.'%')))
   ->groupBy('users.id','users.name','users.email')
   ->select('users.id','users.name','users.email',DB::raw('COUNT(order_items.id) as purchases'),DB::raw('SUM(order_items.total) as spent'),DB::raw('MAX(orders.paid_at) as last_purchase'))
   ->orderByDesc('spent')
   ->paginate(20)->withQueryString();
  $summary=[
   'customers'=>DB::table('order_items')->join('orders','orders.id','=','order_items.order_id')->where('order_items.seller_id',$sellerId)->where('orders.payment_status','paid')->distinct('orders.user_id')->count('orders.user_id'),
   'repeat'=>DB::table(DB::raw('(select orders.user_id from order_items join orders on orders.id=order_items.order_id where order_items.seller_id='.(int)$sellerId." and orders.payment_status='paid' group by orders.user_id having count(distinct orders.id) > 1) as r"))->count(),
  ];
  return view('seller.customers',compact('rows','summary'));
 }
}

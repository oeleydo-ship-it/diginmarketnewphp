<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
class OrderDirectoryController extends Controller
{
 public function index(): View
 {
  $orders=Order::with(['user','items'])->when(request('status'),fn($query,$status)=>$query->where('payment_status',$status))->when(request('q'),fn($query,$term)=>$query->where('number','like','%'.$term.'%'))->latest()->paginate(25)->withQueryString();
  return view('admin.orders.index',compact('orders'));
 }
}

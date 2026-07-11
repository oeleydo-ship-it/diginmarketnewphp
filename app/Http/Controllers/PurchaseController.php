<?php
namespace App\Http\Controllers;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;
class PurchaseController extends Controller
{
 public function index(): View{$orders=auth()->user()->orders()->with('items.license')->latest()->paginate(15);return view('purchases.index',compact('orders'));}
 public function show(Order $order): View{abort_unless($order->user_id===auth()->id(),403);$order->load('items.license.product');foreach($order->items as $item){if($item->license)$item->license->download_url=URL::temporarySignedRoute('downloads.show',now()->addMinutes(10),['license'=>$item->license]);}return view('purchases.show',compact('order'));}
}
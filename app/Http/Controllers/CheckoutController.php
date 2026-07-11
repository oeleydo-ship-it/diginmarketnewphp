<?php
namespace App\Http\Controllers;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
class CheckoutController extends Controller
{
 public function store(CheckoutService $checkout): RedirectResponse{$cart=auth()->user()->cart()->firstOrCreate([],['currency'=>'USD']);$result=$checkout->start($cart,auth()->user());return redirect()->away($result['url']);}
 public function success(Order $order): View{abort_unless($order->user_id===auth()->id(),403);return view('checkout.success',compact('order'));}
}
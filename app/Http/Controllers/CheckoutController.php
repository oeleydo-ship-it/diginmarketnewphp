<?php
namespace App\Http\Controllers;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\PaymentGatewayManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
class CheckoutController extends Controller
{
 public function store(CheckoutService $checkout,PaymentGatewayManager $gateways): RedirectResponse
 {
  $cart=auth()->user()->cart()->firstOrCreate([],['currency'=>'USD']);
  $data=request()->validate(['payment_provider'=>['nullable','string',Rule::in($gateways->availableKeysFor($cart->currency))]]);
  $result=$checkout->start($cart,auth()->user(),$data['payment_provider']??null);
  return redirect()->away($result['url']);
 }
 public function success(Order $order): View{abort_unless($order->user_id===auth()->id(),403);return view('checkout.success',compact('order'));}
 /** Manual-transfer buyers land here instead of a provider; the order stays unpaid until an admin confirms. */
 public function bankTransfer(Order $order): View
 {
  abort_unless($order->user_id===auth()->id(),403);
  abort_unless($order->payment_provider==='bank_transfer',404);
  return view('checkout.bank-transfer',['order'=>$order,'instructions'=>(string)config('services.bank_transfer.instructions')]);
 }
}

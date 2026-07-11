<?php
namespace App\Services;
use App\Contracts\CheckoutGateway;
use App\Models\Order;
use Stripe\StripeClient;
class StripeCheckoutGateway implements CheckoutGateway
{
 public function createCheckout(Order $order): array
 {
  $stripe=new StripeClient((string)config('services.stripe.secret'));
  $session=$stripe->checkout->sessions->create(['mode'=>'payment','customer_email'=>$order->user->email,'line_items'=>$order->items->map(fn($item)=>['quantity'=>1,'price_data'=>['currency'=>strtolower($order->currency),'unit_amount'=>(int)round(((float)$item->total)*100),'product_data'=>['name'=>$item->product_title.' — '.$item->license_name]]])->all(),'success_url'=>route('checkout.success',['order'=>$order]).'?session_id={CHECKOUT_SESSION_ID}','cancel_url'=>route('cart.index'),'metadata'=>['order_id'=>(string)$order->id,'order_number'=>$order->number]]);
  return ['id'=>$session->id,'url'=>$session->url];
 }
}
<?php
namespace App\Services;
use App\Contracts\CheckoutGateway;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class CheckoutService
{
 public function __construct(private CartPricingService $pricing,private CheckoutGateway $gateway,private CommissionResolver $commissions,private AffiliateService $affiliates){}
 public function start(Cart $cart,User $user): array
 {
  abort_if($cart->items()->doesntExist(),422,'Cart is empty.');$totals=$this->pricing->reprice($cart);
  $order=DB::transaction(function()use($cart,$user,$totals){$order=Order::create(['number'=>'DM-'.now()->format('Ymd').'-'.str()->upper(str()->random(10)),'user_id'=>$user->id,'affiliate_profile_id'=>$this->affiliates->resolveForCheckout($user)?->id,'coupon_id'=>$cart->coupon_id,'coupon_code'=>$cart->coupon()->first()?->code,'currency'=>$cart->currency,'subtotal'=>$totals['subtotal'],'discount'=>$totals['discount'],'tax'=>$totals['tax'],'fees'=>$totals['fees'],'total'=>$totals['total'],'customer_ip'=>request()->ip(),'user_agent'=>request()->userAgent()]);foreach($cart->items()->with(['product.seller.sellerProfile','product.versions','licenseType'])->get() as $item){$commission=$this->commissions->resolve($item->product,(float)$item->total);$order->items()->create(['product_id'=>$item->product_id,'seller_id'=>$item->product->seller_id,'product_version_id'=>$item->product->versions->sortByDesc('id')->first()?->id,'license_type_id'=>$item->license_type_id,'product_title'=>$item->product->title,'seller_name'=>$item->product->seller->sellerProfile?->display_name??$item->product->seller->name,'license_name'=>$item->licenseType->name,'unit_price'=>$item->unit_price,'discount'=>$item->discount,'tax'=>$item->tax,'platform_commission'=>$commission['commission'],'seller_earning'=>$commission['seller_earning'],'total'=>$item->total]);}return $order->load(['user','items']);});
  $session=$this->gateway->createCheckout($order);$order->update(['stripe_checkout_session_id'=>$session['id']]);return ['order'=>$order,'url'=>$session['url']];
 }
}

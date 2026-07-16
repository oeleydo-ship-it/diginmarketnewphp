<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(private CartPricingService $pricing, private PaymentGatewayManager $gateways, private CommissionResolver $commissions, private AffiliateService $affiliates) {}

    public function start(Cart $cart, User $user, ?string $provider = null): array
    {
        abort_if($cart->items()->doesntExist(), 422, 'Cart is empty.');
        // Re-check at checkout too: an item added before the buyer became its seller (or via a stale
        // cart) must not slip through to an order that would pay the buyer their own money back.
        abort_if($cart->items()->whereHas('product', fn ($q) => $q->where('seller_id', $user->id))->exists(), 422, 'You cannot purchase your own products. Remove them from the cart to continue.');
        // Re-check the gateway against the cart currency here, not just in the controller: the
        // currency is only final once the cart has been repriced server-side.
        $available = $this->gateways->availableKeysFor($cart->currency);
        abort_if($available === [], 422, 'No payment method is available for this currency.');
        $provider = in_array($provider, $available, true) ? $provider : $available[0];
        $totals = $this->pricing->reprice($cart);
        $order = DB::transaction(function () use ($cart, $user, $totals, $provider) {
            $order = Order::create(['number' => 'DM-'.now()->format('Ymd').'-'.str()->upper(str()->random(10)), 'user_id' => $user->id, 'payment_provider' => $provider, 'affiliate_profile_id' => $this->affiliates->resolveForCheckout($user)?->id, 'coupon_id' => $cart->coupon_id, 'coupon_code' => $cart->coupon()->first()?->code, 'currency' => $cart->currency, 'subtotal' => $totals['subtotal'], 'discount' => $totals['discount'], 'tax' => $totals['tax'], 'fees' => $totals['fees'], 'total' => $totals['total'], 'customer_ip' => request()->ip(), 'user_agent' => request()->userAgent()]);
            foreach ($cart->items()->with(['product.seller.sellerProfile', 'product.versions', 'licenseType'])->get() as $item) {
                $commission = $this->commissions->resolve($item->product, (float) $item->total);
                $order->items()->create(['product_id' => $item->product_id, 'seller_id' => $item->product->seller_id, 'product_version_id' => $item->product->versions->sortByDesc('id')->first()?->id, 'license_type_id' => $item->license_type_id, 'product_title' => $item->product->title, 'seller_name' => $item->product->seller->sellerProfile?->display_name ?? $item->product->seller->name, 'license_name' => $item->licenseType->name, 'unit_price' => $item->unit_price, 'discount' => $item->discount, 'tax' => $item->tax, 'platform_commission' => $commission['commission'], 'seller_earning' => $commission['seller_earning'], 'total' => $item->total]);
            }

return $order->load(['user', 'items']);
        });
        $session = $this->gateways->driver($provider)->createCheckout($order);
        $order->update(['provider_checkout_id' => $session['id']]);

        return ['order' => $order, 'url' => $session['url'], 'provider' => $provider];
    }
}

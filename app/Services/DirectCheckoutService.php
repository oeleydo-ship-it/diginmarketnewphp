<?php

namespace App\Services;

use App\Models\Bundle;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Gateway checkout for purchases that don't go through the cart: bundles (one order item per
 * included product, bundle price allocated pro-rata) and support extensions (one item that
 * lengthens an existing license). Reuses the same order → gateway → webhook fulfilment path.
 */
class DirectCheckoutService
{
    public function __construct(private PaymentGatewayManager $gateways, private CommissionResolver $commissions) {}

    /** @return array{order:Order,url:string} */
    public function startBundle(Bundle $bundle, User $buyer, ?string $provider = null): array
    {
        abort_unless($bundle->isPurchasable(), 422, 'This bundle is not available right now.');
        $currency = strtoupper((string) config('marketplace.currency', 'USD'));
        $provider = $this->resolveProvider($provider, $currency);
        $order = DB::transaction(function () use ($bundle, $buyer, $currency, $provider) {
            $products = $bundle->products()->with(['seller.sellerProfile', 'versions'])->get();
            $regular = LicenseType::where('slug', 'regular')->firstOrFail();
            // Allocate the bundle price across products proportionally to their regular prices, in
            // cents, with the last product absorbing rounding — totals must sum exactly to the charge.
            $bundleCents = (int) round(((float) $bundle->price) * 100);
            $baseCents = $products->map(fn ($p) => (int) round(((float) $p->regular_price) * 100));
            $baseTotal = max(1, $baseCents->sum());
            $order = Order::create(['number' => 'DM-'.now()->format('Ymd').'-'.str()->upper(str()->random(10)), 'user_id' => $buyer->id, 'payment_provider' => $provider, 'currency' => $currency, 'subtotal' => $bundleCents / 100, 'discount' => 0, 'tax' => 0, 'fees' => 0, 'total' => $bundleCents / 100, 'customer_ip' => request()->ip(), 'user_agent' => request()->userAgent()]);
            $allocated = 0;
            foreach ($products as $index => $product) {
                $share = $index === $products->count() - 1 ? $bundleCents - $allocated : (int) floor($bundleCents * $baseCents[$index] / $baseTotal);
                $allocated += $share;
                $commission = $this->commissions->resolve($product, $share / 100);
                $order->items()->create(['product_id' => $product->id, 'seller_id' => $product->seller_id, 'product_version_id' => $product->versions->sortByDesc('id')->first()?->id, 'license_type_id' => $regular->id, 'item_type' => 'product', 'product_title' => $product->title, 'seller_name' => $product->seller->sellerProfile?->display_name ?? $product->seller->name, 'license_name' => $regular->name.' (bundle)', 'unit_price' => $share / 100, 'platform_commission' => $commission['commission'], 'seller_earning' => $commission['seller_earning'], 'total' => $share / 100]);
            }

            return $order->load(['user', 'items']);
        });

        return $this->redirectToGateway($order, $provider);
    }

    /** @return array{order:Order,url:string} */
    public function startSupportExtension(License $license, User $buyer, ?string $provider = null): array
    {
        abort_unless($license->user_id === $buyer->id, 403);
        abort_unless($license->status === 'active', 422, 'Support can only be extended on an active license.');
        $product = $license->product()->firstOrFail();
        abort_if($product->support_extension_price === null, 422, 'This product does not offer support extensions.');
        $currency = strtoupper((string) config('marketplace.currency', 'USD'));
        $provider = $this->resolveProvider($provider, $currency);
        $order = DB::transaction(function () use ($license, $buyer, $product, $currency, $provider) {
            $price = (float) $product->support_extension_price;
            $commission = $this->commissions->resolve($product, $price);
            $order = Order::create(['number' => 'DM-'.now()->format('Ymd').'-'.str()->upper(str()->random(10)), 'user_id' => $buyer->id, 'payment_provider' => $provider, 'currency' => $currency, 'subtotal' => $price, 'discount' => 0, 'tax' => 0, 'fees' => 0, 'total' => $price, 'customer_ip' => request()->ip(), 'user_agent' => request()->userAgent()]);
            $order->items()->create(['product_id' => $product->id, 'seller_id' => $product->seller_id, 'product_version_id' => $license->product_version_id, 'license_type_id' => $license->license_type_id, 'item_type' => 'support_extension', 'license_id' => $license->id, 'product_title' => $product->title, 'seller_name' => $product->seller()->value('name'), 'license_name' => 'Support extension +'.$product->support_extension_months.' months', 'unit_price' => $price, 'platform_commission' => $commission['commission'], 'seller_earning' => $commission['seller_earning'], 'total' => $price]);

            return $order->load(['user', 'items']);
        });

        return $this->redirectToGateway($order, $provider);
    }

    private function resolveProvider(?string $provider, string $currency): string
    {
        $available = $this->gateways->availableKeysFor($currency);
        abort_if($available === [], 422, 'No payment method is available for this currency.');

        return in_array($provider, $available, true) ? $provider : $available[0];
    }

    /** @return array{order:Order,url:string} */
    private function redirectToGateway(Order $order, string $provider): array
    {
        $session = $this->gateways->driver($provider)->createCheckout($order);
        $order->update(['provider_checkout_id' => $session['id']]);

        return ['order' => $order, 'url' => $session['url']];
    }
}

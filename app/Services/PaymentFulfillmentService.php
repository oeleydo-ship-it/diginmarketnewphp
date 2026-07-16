<?php

namespace App\Services;

use App\Models\License;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class PaymentFulfillmentService
{
    public function __construct(private SellerWalletService $wallets, private AffiliateService $affiliates, private MarketplaceMailer $mailer, private CouponService $coupons) {}

    /** @param string|null $provider Falls back to the gateway the order was started with. */
    public function fulfill(int $orderId, string $paymentId, ?string $provider = null, array $payload = []): Order
    {
        $wasPaid = Order::whereKey($orderId)->value('payment_status') === 'paid';
        $order = DB::transaction(function () use ($orderId, $paymentId, $provider, $payload) {
            $order = Order::query()->lockForUpdate()->with('items')->findOrFail($orderId);
            if ($order->payment_status === 'paid') {
                return $order;
            }$order->payments()->create(['provider' => $provider ?? $order->payment_provider ?? 'stripe', 'provider_payment_id' => $paymentId, 'amount' => $order->total, 'currency' => $order->currency, 'status' => 'succeeded', 'payload' => $payload, 'paid_at' => now()]);
            $order->update(['payment_status' => 'paid', 'status' => 'completed', 'paid_at' => now()]);
            foreach ($order->items as $item) {
                if ($item->item_type === 'support_extension') {
                    $this->extendSupport($item);
                } else {
                    License::firstOrCreate(['order_item_id' => $item->id], ['license_key' => 'DM-'.str()->upper(str()->random(8).'-'.str()->random(8).'-'.str()->random(8)), 'product_id' => $item->product_id, 'product_version_id' => $item->product_version_id, 'user_id' => $order->user_id, 'license_type_id' => $item->license_type_id, 'status' => 'active', 'activation_limit' => 1, 'support_expires_at' => now()->addMonths(6)]);
                    $item->product()->increment('sales_count');
                }$this->wallets->creditSale($item, $order->currency);
            }$this->affiliates->creditReferral($order);
            $this->coupons->redeem($order);

            return $order->fresh(['items.license']);
        });
        if (! $wasPaid && $order->payment_status === 'paid') {
            $this->mailer->orderPaid($order);
        }

        return $order;
    }

    /** Lengthen the referenced license from whichever is later: its current expiry or now. */
    private function extendSupport(OrderItem $item): void
    {
        $license = $item->extendedLicense()->lockForUpdate()->first();
        if (! $license) {
            return;
        }
        $months = (int) ($item->product()->value('support_extension_months') ?: 6);
        $base = $license->support_expires_at && $license->support_expires_at->isFuture() ? $license->support_expires_at : now();
        $license->update(['support_expires_at' => $base->copy()->addMonths($months)]);
    }
}

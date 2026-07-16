<?php

namespace App\Services\Gateways;

use App\Contracts\CheckoutGateway;
use App\Models\Order;

abstract class Gateway implements CheckoutGateway
{
    /**
     * Provider APIs bill in minor units (cents/paise/kobo). Money is stored as a decimal string,
     * so scale first and round once — never multiply a float that has already lost precision.
     */
    protected function minorUnits(Order $order): int
    {
        $factor = in_array(strtoupper($order->currency), (array) config('payments.zero_decimal_currencies'), true) ? 1 : 100;

        return (int) round(((float) $order->total) * $factor);
    }

    /** Decimal string in major units, the format PayPal expects. */
    protected function majorUnits(Order $order): string
    {
        return number_format((float) $order->total, in_array(strtoupper($order->currency), (array) config('payments.zero_decimal_currencies'), true) ? 0 : 2, '.', '');
    }

    protected function successUrl(Order $order): string
    {
        return route('checkout.success', ['order' => $order]);
    }

    protected function cancelUrl(): string
    {
        return route('cart.index');
    }

    /** Constant-time comparison guards signature checks against timing oracles. */
    protected function signatureMatches(string $expected, string $received): bool
    {
        return hash_equals($expected, $received);
    }

    /** Default: no synchronous verification — the webhook is the only confirmation path. */
    public function verifyReturn(Order $order): ?array
    {
        return null;
    }
}

<?php

namespace App\Services;

use App\Contracts\RefundGateway;
use App\Models\Payment;

/**
 * Routes a refund to the gateway that took the payment. Before multi-gateway checkout this
 * was hardwired to Stripe, which silently broke refunds for every other provider.
 */
class ProviderRefundGateway implements RefundGateway
{
    public function __construct(private PaymentGatewayManager $gateways) {}

    public function refund(Payment $payment, float $amount): array
    {
        return $this->gateways->driver($payment->provider ?: 'stripe')->refund($payment, $amount);
    }
}

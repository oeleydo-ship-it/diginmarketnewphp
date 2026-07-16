<?php

namespace App\Services\Gateways;

use App\Models\Order;

class BankTransferGateway extends Gateway
{
    public function key(): string
    {
        return 'bank_transfer';
    }

    /** Usable as soon as an administrator has published transfer instructions for buyers to follow. */
    public function isConfigured(): bool
    {
        return (bool) config('services.bank_transfer.instructions');
    }

    /**
     * There is no provider to redirect to: the buyer lands on an instructions page and the order
     * stays unpaid until an administrator confirms the funds arrived.
     */
    public function createCheckout(Order $order): array
    {
        return ['id' => 'bt_'.$order->number, 'url' => route('checkout.bank-transfer', ['order' => $order])];
    }

    public function parseWebhook(string $payload, array $headers): ?array
    {
        return null;
    }
}

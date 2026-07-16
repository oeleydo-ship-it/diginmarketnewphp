<?php

namespace App\Contracts;

use App\Models\Order;

interface CheckoutGateway
{
    /** Registry key in config/payments.php; also the value persisted on orders.payment_provider. */
    public function key(): string;

    /** False when credentials are absent, so the driver is hidden at checkout instead of failing mid-purchase. */
    public function isConfigured(): bool;

    /** @return array{id:string,url:string} Provider checkout reference and the URL to send the buyer to. */
    public function createCheckout(Order $order): array;

    /**
     * Verify and normalise an inbound webhook. Throws when the signature is invalid.
     * Returns null when the payload carries no event this driver acts on.
     *
     * @return array{event_id:string,type:string,order_id:?int,payment_id:?string,paid:bool}|null
     */
    public function parseWebhook(string $payload, array $headers): ?array;

    /**
     * Ask the provider's API (server-side, never the browser) whether this order is paid, so the
     * buyer's return from checkout can fulfil immediately instead of waiting for the webhook.
     * Returns null when unpaid or when the driver has no synchronous verification.
     *
     * @return array{payment_id:string,payload?:array}|null
     */
    public function verifyReturn(Order $order): ?array;
}

<?php

namespace App\Services\Gateways;

use App\Models\Order;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeCheckoutGateway extends Gateway
{
    public function key(): string
    {
        return 'stripe';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.stripe.secret');
    }

    public function createCheckout(Order $order): array
    {
        $stripe = new StripeClient((string) config('services.stripe.secret'));
        $session = $stripe->checkout->sessions->create(['mode' => 'payment', 'customer_email' => $order->user->email, 'line_items' => $order->items->map(fn ($item) => ['quantity' => 1, 'price_data' => ['currency' => strtolower($order->currency), 'unit_amount' => (int) round(((float) $item->total) * (in_array(strtoupper($order->currency), (array) config('payments.zero_decimal_currencies'), true) ? 1 : 100)), 'product_data' => ['name' => $item->product_title.' — '.$item->license_name]]])->all(), 'success_url' => $this->successUrl($order).'?session_id={CHECKOUT_SESSION_ID}', 'cancel_url' => $this->cancelUrl(), 'metadata' => ['order_id' => (string) $order->id, 'order_number' => $order->number]]);

        return ['id' => $session->id, 'url' => $session->url];
    }

    /** Retrieve the stored checkout session from Stripe's API and confirm it is actually paid. */
    public function verifyReturn(Order $order): ?array
    {
        if (! $order->provider_checkout_id || ! $this->isConfigured()) {
            return null;
        }
        $stripe = new StripeClient((string) config('services.stripe.secret'));
        $session = $stripe->checkout->sessions->retrieve($order->provider_checkout_id);
        if ($session->payment_status !== 'paid') {
            return null;
        }

        return ['payment_id' => (string) ($session->payment_intent ?: $session->id), 'payload' => ['source' => 'return_verification', 'session_id' => $session->id, 'payment_status' => $session->payment_status]];
    }

    public function parseWebhook(string $payload, array $headers): ?array
    {
        $event = Webhook::constructEvent($payload, (string) ($headers['stripe-signature'] ?? ''), (string) config('services.stripe.webhook_secret'));
        if ($event->type !== 'checkout.session.completed') {
            return null;
        }
        $session = $event->data->object;

        return ['event_id' => $event->id, 'type' => $event->type, 'order_id' => isset($session->metadata->order_id) ? (int) $session->metadata->order_id : null, 'payment_id' => (string) ($session->payment_intent ?: $session->id), 'paid' => true];
    }
}

<?php

namespace App\Services\Gateways;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RazorpayCheckoutGateway extends Gateway
{
    public function key(): string
    {
        return 'razorpay';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.razorpay.key') && (bool) config('services.razorpay.secret');
    }

    public function createCheckout(Order $order): array
    {
        $response = Http::withBasicAuth((string) config('services.razorpay.key'), (string) config('services.razorpay.secret'))->post('https://api.razorpay.com/v1/payment_links', ['amount' => $this->minorUnits($order), 'currency' => strtoupper($order->currency), 'accept_partial' => false, 'reference_id' => $order->number, 'description' => config('app.name').' order '.$order->number, 'customer' => ['email' => $order->user->email, 'name' => $order->user->name], 'notify' => ['email' => false, 'sms' => false], 'notes' => ['order_id' => (string) $order->id], 'callback_url' => $this->successUrl($order), 'callback_method' => 'get']);
        if ($response->failed()) {
            throw new RuntimeException('Razorpay payment link creation failed: '.$response->body());
        }

        return ['id' => (string) $response->json('id'), 'url' => (string) $response->json('short_url')];
    }

    public function parseWebhook(string $payload, array $headers): ?array
    {
        $secret = (string) config('services.razorpay.webhook_secret');
        if (! $this->signatureMatches(hash_hmac('sha256', $payload, $secret), (string) ($headers['x-razorpay-signature'] ?? ''))) {
            throw new RuntimeException('Razorpay webhook signature verification failed.');
        }
        $event = json_decode($payload, true) ?: [];
        if (($event['event'] ?? '') !== 'payment_link.paid') {
            return null;
        }
        $link = $event['payload']['payment_link']['entity'] ?? [];
        $payment = $event['payload']['payment']['entity'] ?? [];

        // Razorpay omits a top-level event id, but the payment link id is unique per order and is
        // what the retry sends again, so it is the right idempotency key here.
        return ['event_id' => 'payment_link.paid:'.($link['id'] ?? ''), 'type' => 'payment_link.paid', 'order_id' => isset($link['notes']['order_id']) ? (int) $link['notes']['order_id'] : null, 'payment_id' => (string) ($payment['id'] ?? $link['id'] ?? ''), 'paid' => true];
    }
}

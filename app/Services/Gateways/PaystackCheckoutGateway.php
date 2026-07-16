<?php

namespace App\Services\Gateways;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackCheckoutGateway extends Gateway
{
    public function key(): string
    {
        return 'paystack';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.paystack.secret');
    }

    public function createCheckout(Order $order): array
    {
        $response = Http::withToken((string) config('services.paystack.secret'))->post('https://api.paystack.co/transaction/initialize', ['email' => $order->user->email, 'amount' => $this->minorUnits($order), 'currency' => strtoupper($order->currency), 'reference' => $order->number, 'callback_url' => $this->successUrl($order), 'metadata' => ['order_id' => (string) $order->id, 'order_number' => $order->number]]);
        if ($response->failed() || ! $response->json('status')) {
            throw new RuntimeException('Paystack transaction initialisation failed: '.$response->body());
        }

        return ['id' => (string) $response->json('data.reference'), 'url' => (string) $response->json('data.authorization_url')];
    }

    /** Paystack's verify endpoint confirms a transaction by reference (we use the order number). */
    public function verifyReturn(Order $order): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }
        $response = Http::withToken((string) config('services.paystack.secret'))->get('https://api.paystack.co/transaction/verify/'.rawurlencode($order->number));
        if ($response->failed() || $response->json('data.status') !== 'success') {
            return null;
        }

        return ['payment_id' => (string) ($order->number), 'payload' => ['source' => 'return_verification', 'paystack_id' => $response->json('data.id')]];
    }

    public function parseWebhook(string $payload, array $headers): ?array
    {
        // Paystack signs with SHA512 over the raw body using the same secret key as the API.
        $secret = (string) config('services.paystack.secret');
        if (! $this->signatureMatches(hash_hmac('sha512', $payload, $secret), (string) ($headers['x-paystack-signature'] ?? ''))) {
            throw new RuntimeException('Paystack webhook signature verification failed.');
        }
        $event = json_decode($payload, true) ?: [];
        if (($event['event'] ?? '') !== 'charge.success') {
            return null;
        }
        $data = $event['data'] ?? [];

        return ['event_id' => 'charge.success:'.($data['id'] ?? $data['reference'] ?? ''), 'type' => 'charge.success', 'order_id' => isset($data['metadata']['order_id']) ? (int) $data['metadata']['order_id'] : null, 'payment_id' => (string) ($data['reference'] ?? ''), 'paid' => true];
    }
}

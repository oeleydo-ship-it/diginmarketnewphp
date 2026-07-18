<?php

namespace App\Services\Gateways;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FlutterwaveCheckoutGateway extends Gateway
{
    private const API = 'https://api.flutterwave.com/v3';

    public function key(): string
    {
        return 'flutterwave';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.flutterwave.secret');
    }

    public function createCheckout(Order $order): array
    {
        $response = Http::withToken((string) config('services.flutterwave.secret'))->post(self::API.'/payments', [
            'tx_ref' => $order->number,
            'amount' => (float) $order->total,
            'currency' => strtoupper($order->currency),
            'redirect_url' => $this->successUrl($order),
            'customer' => ['email' => $order->user->email, 'name' => $order->user->name],
            'meta' => ['order_id' => (string) $order->id],
            'customizations' => ['title' => (string) config('app.name')],
        ]);
        if ($response->failed() || $response->json('status') !== 'success') {
            throw new RuntimeException('Flutterwave payment creation failed: '.$response->body());
        }

        return ['id' => (string) $order->number, 'url' => (string) $response->json('data.link')];
    }

    public function parseWebhook(string $payload, array $headers): ?array
    {
        // Flutterwave signs deliveries by echoing the merchant-chosen secret hash verbatim.
        $expected = (string) config('services.flutterwave.secret_hash');
        if ($expected === '' || ! $this->signatureMatches($expected, (string) ($headers['verif-hash'] ?? ''))) {
            throw new RuntimeException('Flutterwave webhook signature verification failed.');
        }
        $event = json_decode($payload, true) ?: [];
        if (($event['event'] ?? '') !== 'charge.completed' || (($event['data']['status'] ?? '') !== 'successful')) {
            return null;
        }
        $data = $event['data'];

        return ['event_id' => 'charge.completed:'.($data['id'] ?? $data['tx_ref'] ?? ''), 'type' => 'charge.completed', 'order_id' => isset($data['meta']['order_id']) ? (int) $data['meta']['order_id'] : $this->orderIdFromReference((string) ($data['tx_ref'] ?? '')), 'payment_id' => (string) ($data['id'] ?? ''), 'paid' => true];
    }

    public function verifyReturn(Order $order): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }
        $response = Http::withToken((string) config('services.flutterwave.secret'))->get(self::API.'/transactions/verify_by_reference', ['tx_ref' => $order->number]);
        if ($response->failed() || $response->json('data.status') !== 'successful') {
            return null;
        }

        return ['payment_id' => (string) $response->json('data.id'), 'payload' => ['source' => 'return_verification']];
    }

    /** The stored payment id is Flutterwave's numeric transaction id — what the refund API wants. */
    public function refund(Payment $payment, float $amount): array
    {
        $response = Http::withToken((string) config('services.flutterwave.secret'))->post(self::API.'/transactions/'.$payment->provider_payment_id.'/refund', ['amount' => $amount]);
        if ($response->failed() || $response->json('status') !== 'success') {
            throw new RuntimeException('Flutterwave refund failed: '.$response->body());
        }

        return ['id' => (string) ($response->json('data.id') ?? $payment->provider_payment_id), 'status' => 'succeeded'];
    }

    /** Some webhook payloads omit meta; fall back to resolving our order number reference. */
    private function orderIdFromReference(string $reference): ?int
    {
        return $reference === '' ? null : Order::where('number', $reference)->value('id');
    }
}

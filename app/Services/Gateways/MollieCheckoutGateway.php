<?php

namespace App\Services\Gateways;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MollieCheckoutGateway extends Gateway
{
    private const API = 'https://api.mollie.com/v2';

    public function key(): string
    {
        return 'mollie';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.mollie.api_key');
    }

    public function createCheckout(Order $order): array
    {
        $response = Http::withToken((string) config('services.mollie.api_key'))->post(self::API.'/payments', [
            'amount' => ['currency' => strtoupper($order->currency), 'value' => number_format((float) $order->total, 2, '.', '')],
            'description' => config('app.name').' order '.$order->number,
            'redirectUrl' => $this->successUrl($order),
            'webhookUrl' => route('payments.webhook', ['provider' => 'mollie']),
            'metadata' => ['order_id' => (string) $order->id],
        ]);
        if ($response->failed() || ! $response->json('id')) {
            throw new RuntimeException('Mollie payment creation failed: '.$response->body());
        }

        return ['id' => (string) $response->json('id'), 'url' => (string) $response->json('_links.checkout.href')];
    }

    /**
     * Mollie webhooks carry only `id=tr_x` and no signature — verification IS fetching the
     * payment back from Mollie's API with our key and trusting that response alone.
     */
    public function parseWebhook(string $payload, array $headers): ?array
    {
        parse_str($payload, $body);
        $paymentId = (string) ($body['id'] ?? '');
        if ($paymentId === '') {
            return null;
        }
        $payment = Http::withToken((string) config('services.mollie.api_key'))->get(self::API.'/payments/'.$paymentId);
        if ($payment->failed()) {
            throw new RuntimeException('Mollie payment lookup failed: '.$payment->body());
        }
        if ($payment->json('status') !== 'paid') {
            return null;
        }

        return ['event_id' => 'paid:'.$paymentId, 'type' => 'payment.paid', 'order_id' => (int) $payment->json('metadata.order_id'), 'payment_id' => $paymentId, 'paid' => true];
    }

    public function verifyReturn(Order $order): ?array
    {
        if (! $order->provider_checkout_id || ! $this->isConfigured()) {
            return null;
        }
        $payment = Http::withToken((string) config('services.mollie.api_key'))->get(self::API.'/payments/'.$order->provider_checkout_id);
        if ($payment->failed() || $payment->json('status') !== 'paid') {
            return null;
        }

        return ['payment_id' => (string) $order->provider_checkout_id, 'payload' => ['source' => 'return_verification']];
    }

    public function refund(Payment $payment, float $amount): array
    {
        $response = Http::withToken((string) config('services.mollie.api_key'))->post(self::API.'/payments/'.$payment->provider_payment_id.'/refunds', [
            'amount' => ['currency' => strtoupper($payment->currency), 'value' => number_format($amount, 2, '.', '')],
        ]);
        if ($response->failed()) {
            throw new RuntimeException('Mollie refund failed: '.$response->body());
        }

        return ['id' => (string) $response->json('id'), 'status' => in_array($response->json('status'), ['refunded', 'pending', 'queued', 'processing'], true) ? 'succeeded' : (string) $response->json('status')];
    }
}

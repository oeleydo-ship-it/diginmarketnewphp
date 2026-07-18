<?php

namespace App\Services\Gateways;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstamojoCheckoutGateway extends Gateway
{
    public function key(): string
    {
        return 'instamojo';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.instamojo.api_key') && (bool) config('services.instamojo.auth_token');
    }

    private function base(): string
    {
        return config('services.instamojo.mode') === 'live' ? 'https://www.instamojo.com/api/1.1' : 'https://test.instamojo.com/api/1.1';
    }

    private function headers(): array
    {
        return ['X-Api-Key' => (string) config('services.instamojo.api_key'), 'X-Auth-Token' => (string) config('services.instamojo.auth_token')];
    }

    public function createCheckout(Order $order): array
    {
        $response = Http::withHeaders($this->headers())->asForm()->post($this->base().'/payment-requests/', [
            'purpose' => config('app.name').' order '.$order->number,
            'amount' => number_format((float) $order->total, 2, '.', ''),
            'buyer_name' => $order->user->name,
            'email' => $order->user->email,
            'redirect_url' => $this->successUrl($order),
            'webhook' => route('payments.webhook', ['provider' => 'instamojo']),
            'allow_repeated_payments' => 'False',
        ]);
        if ($response->failed() || ! $response->json('success')) {
            throw new RuntimeException('Instamojo payment request failed: '.$response->body());
        }

        return ['id' => (string) $response->json('payment_request.id'), 'url' => (string) $response->json('payment_request.longurl')];
    }

    /**
     * Instamojo signs the form body with HMAC-SHA1 over the |-joined values of the keys sorted
     * alphabetically (mac excluded), using the account salt. The order is resolved from the
     * payment_request id we stored at checkout.
     */
    public function parseWebhook(string $payload, array $headers): ?array
    {
        parse_str($payload, $body);
        $mac = (string) ($body['mac'] ?? '');
        unset($body['mac']);
        ksort($body);
        $expected = hash_hmac('sha1', implode('|', array_map('strval', $body)), (string) config('services.instamojo.salt'));
        if ($mac === '' || ! $this->signatureMatches($expected, $mac)) {
            throw new RuntimeException('Instamojo webhook signature verification failed.');
        }
        if (($body['status'] ?? '') !== 'Credit') {
            return null;
        }
        $orderId = Order::where('provider_checkout_id', (string) ($body['payment_request_id'] ?? ''))->value('id');

        return ['event_id' => 'credit:'.($body['payment_id'] ?? ''), 'type' => 'payment.credit', 'order_id' => $orderId ? (int) $orderId : null, 'payment_id' => (string) ($body['payment_id'] ?? ''), 'paid' => true];
    }

    public function refund(Payment $payment, float $amount): array
    {
        $response = Http::withHeaders($this->headers())->asForm()->post($this->base().'/refunds/', [
            'payment_id' => $payment->provider_payment_id,
            'type' => 'PTH', // problems with the merchandise/service — the generic merchant-initiated reason
            'refund_amount' => number_format($amount, 2, '.', ''),
            'body' => 'Refund approved by the marketplace.',
        ]);
        if ($response->failed() || ! $response->json('success')) {
            throw new RuntimeException('Instamojo refund failed: '.$response->body());
        }

        return ['id' => (string) ($response->json('refund.id') ?? $payment->provider_payment_id), 'status' => 'succeeded'];
    }
}

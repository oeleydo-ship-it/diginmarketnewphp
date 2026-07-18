<?php

namespace App\Services\Gateways;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SslCommerzCheckoutGateway extends Gateway
{
    public function key(): string
    {
        return 'sslcommerz';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.sslcommerz.store_id') && (bool) config('services.sslcommerz.store_password');
    }

    private function base(): string
    {
        return config('services.sslcommerz.mode') === 'live' ? 'https://securepay.sslcommerz.com' : 'https://sandbox.sslcommerz.com';
    }

    public function createCheckout(Order $order): array
    {
        $response = Http::asForm()->post($this->base().'/gwprocess/v4/api.php', [
            'store_id' => (string) config('services.sslcommerz.store_id'),
            'store_passwd' => (string) config('services.sslcommerz.store_password'),
            'total_amount' => number_format((float) $order->total, 2, '.', ''),
            'currency' => strtoupper($order->currency),
            'tran_id' => $order->number,
            'success_url' => $this->successUrl($order),
            'fail_url' => $this->cancelUrl(),
            'cancel_url' => $this->cancelUrl(),
            'ipn_url' => route('payments.webhook', ['provider' => 'sslcommerz']),
            'value_a' => (string) $order->id,
            'cus_name' => $order->user->name,
            'cus_email' => $order->user->email,
            'cus_add1' => 'N/A', 'cus_city' => 'N/A', 'cus_country' => 'N/A', 'cus_phone' => 'N/A',
            'product_name' => config('app.name').' order', 'product_category' => 'digital', 'product_profile' => 'non-physical-goods',
            'shipping_method' => 'NO', 'num_of_item' => max(1, $order->items->count()),
        ]);
        if ($response->failed() || $response->json('status') !== 'SUCCESS') {
            throw new RuntimeException('SslCommerz session creation failed: '.$response->body());
        }

        return ['id' => (string) $response->json('sessionkey'), 'url' => (string) $response->json('GatewayPageURL')];
    }

    /**
     * IPN payloads are validated by handing the val_id back to SslCommerz's validation API with
     * our store credentials — the response, not the inbound POST, is the source of truth.
     */
    public function parseWebhook(string $payload, array $headers): ?array
    {
        parse_str($payload, $body);
        $valId = (string) ($body['val_id'] ?? '');
        if ($valId === '') {
            return null;
        }
        $validation = Http::get($this->base().'/validator/api/validationserverAPI.php', [
            'val_id' => $valId,
            'store_id' => (string) config('services.sslcommerz.store_id'),
            'store_passwd' => (string) config('services.sslcommerz.store_password'),
            'format' => 'json',
        ]);
        if ($validation->failed()) {
            throw new RuntimeException('SslCommerz validation call failed: '.$validation->body());
        }
        if (! in_array($validation->json('status'), ['VALID', 'VALIDATED'], true)) {
            return null;
        }
        $orderId = Order::where('number', (string) $validation->json('tran_id'))->value('id');

        return ['event_id' => 'valid:'.$valId, 'type' => 'payment.valid', 'order_id' => $orderId ? (int) $orderId : null, 'payment_id' => $valId, 'paid' => true];
    }

    /**
     * SslCommerz refunds need the bank_tran_id and run through their merchant panel. Like bank
     * transfer, approving here records the manual refund so order/license/ledger state updates.
     */
    public function refund(Payment $payment, float $amount): array
    {
        return ['id' => 'manual-refund:'.$payment->id, 'status' => 'succeeded'];
    }
}

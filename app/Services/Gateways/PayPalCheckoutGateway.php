<?php

namespace App\Services\Gateways;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalCheckoutGateway extends Gateway
{
    public function key(): string
    {
        return 'paypal';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.paypal.client_id') && (bool) config('services.paypal.secret');
    }

    private function baseUrl(): string
    {
        return config('services.paypal.mode') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    private function token(): string
    {
        $response = Http::asForm()->withBasicAuth((string) config('services.paypal.client_id'), (string) config('services.paypal.secret'))->post($this->baseUrl().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);
        if ($response->failed()) {
            throw new RuntimeException('PayPal authentication failed: '.$response->body());
        }

        return (string) $response->json('access_token');
    }

    public function createCheckout(Order $order): array
    {
        // custom_id carries our order id through to the capture webhook, where PayPal echoes it back.
        $response = Http::withToken($this->token())->post($this->baseUrl().'/v2/checkout/orders', ['intent' => 'CAPTURE', 'purchase_units' => [['reference_id' => $order->number, 'custom_id' => (string) $order->id, 'description' => config('app.name').' order '.$order->number, 'amount' => ['currency_code' => strtoupper($order->currency), 'value' => $this->majorUnits($order)]]], 'application_context' => ['brand_name' => (string) config('app.name'), 'user_action' => 'PAY_NOW', 'return_url' => $this->successUrl($order), 'cancel_url' => $this->cancelUrl()]]);
        if ($response->failed()) {
            throw new RuntimeException('PayPal order creation failed: '.$response->body());
        }
        $approve = collect($response->json('links', []))->firstWhere('rel', 'approve')['href'] ?? null;
        if (! $approve) {
            throw new RuntimeException('PayPal did not return an approval link.');
        }

        return ['id' => (string) $response->json('id'), 'url' => $approve];
    }

    public function parseWebhook(string $payload, array $headers): ?array
    {
        $event = json_decode($payload, true) ?: [];
        $verification = Http::withToken($this->token())->post($this->baseUrl().'/v1/notifications/verify-webhook-signature', ['auth_algo' => $headers['paypal-auth-algo'] ?? '', 'cert_url' => $headers['paypal-cert-url'] ?? '', 'transmission_id' => $headers['paypal-transmission-id'] ?? '', 'transmission_sig' => $headers['paypal-transmission-sig'] ?? '', 'transmission_time' => $headers['paypal-transmission-time'] ?? '', 'webhook_id' => (string) config('services.paypal.webhook_id'), 'webhook_event' => $event]);
        if ($verification->json('verification_status') !== 'SUCCESS') {
            throw new RuntimeException('PayPal webhook signature verification failed.');
        }
        $type = (string) ($event['event_type'] ?? '');
        // An approved order is not yet money: capture it, which makes PayPal emit the CAPTURE.COMPLETED
        // event we actually fulfil on. Doing this here (not on the return URL) means an abandoned
        // browser tab still completes the purchase.
        if ($type === 'CHECKOUT.ORDER.APPROVED') {
            $this->capture((string) ($event['resource']['id'] ?? ''));

            return null;
        }
        if ($type !== 'PAYMENT.CAPTURE.COMPLETED') {
            return null;
        }
        $resource = $event['resource'] ?? [];

        return ['event_id' => (string) ($event['id'] ?? ''), 'type' => $type, 'order_id' => isset($resource['custom_id']) ? (int) $resource['custom_id'] : null, 'payment_id' => (string) ($resource['id'] ?? ''), 'paid' => true];
    }

    private function capture(string $paypalOrderId): void
    {
        if ($paypalOrderId === '') {
            return;
        }
        $response = Http::withToken($this->token())->withHeaders(['PayPal-Request-Id' => 'capture-'.$paypalOrderId])->post($this->baseUrl().'/v2/checkout/orders/'.$paypalOrderId.'/capture');
        // 422 ORDER_ALREADY_CAPTURED is expected when the buyer's return and the webhook race; ignore it.
        if ($response->failed() && ! str_contains($response->body(), 'ORDER_ALREADY_CAPTURED')) {
            throw new RuntimeException('PayPal capture failed: '.$response->body());
        }
    }
}

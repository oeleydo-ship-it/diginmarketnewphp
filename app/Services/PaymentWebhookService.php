<?php

namespace App\Services;

use App\Models\PaymentWebhookEvent;

class PaymentWebhookService
{
    public function __construct(private PaymentFulfillmentService $fulfillment, private PaymentGatewayManager $gateways) {}

    /**
     * Verify, record, then process exactly once. The (provider, event_id) row is claimed before any
     * fulfilment work, so a provider replaying the same delivery cannot issue a second license.
     *
     * @param  array<string,string>  $headers  Lower-cased header name => value.
     */
    public function handle(string $provider, string $payload, array $headers): void
    {
        $event = $this->gateways->driver($provider)->parseWebhook($payload, $headers);
        if (! $event) {
            return;
        }
        $record = PaymentWebhookEvent::firstOrCreate(['provider' => $provider, 'event_id' => $event['event_id']], ['event_type' => $event['type'], 'payload' => json_decode($payload, true), 'processing_status' => 'pending']);
        if (! $record->wasRecentlyCreated || $record->processing_status === 'processed') {
            return;
        }
        try {
            if ($event['paid'] && $event['order_id']) {
                $this->fulfillment->fulfill($event['order_id'], (string) $event['payment_id'], $provider, json_decode($payload, true));
            }
            $record->update(['processing_status' => 'processed', 'processed_at' => now()]);
        } catch (\Throwable $e) {
            $record->update(['processing_status' => 'failed', 'error_message' => $e->getMessage(), 'retry_count' => $record->retry_count + 1]);
            throw $e;
        }
    }
}

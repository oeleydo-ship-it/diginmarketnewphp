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
        // Some providers (Mollie, Instamojo, SslCommerz) deliver form-encoded bodies, not JSON.
        $decoded = json_decode($payload, true) ?? ['raw' => $payload];
        $record = PaymentWebhookEvent::firstOrCreate(['provider' => $provider, 'event_id' => $event['event_id']], ['event_type' => $event['type'], 'payload' => $decoded, 'processing_status' => 'pending']);
        if ($record->processing_status === 'processed') {
            return;
        }
        // A delivery that previously errored must stay retryable: providers redeliver failed webhooks
        // for days, and returning early on "not recently created" meant the very first transient
        // failure (a deadlock, a mail hiccup) left the order permanently unfulfilled. Claim the row
        // with a conditional UPDATE so concurrent deliveries still cannot both process it; a claim
        // abandoned by a crashed worker goes stale after a few minutes and can be retried.
        if (! $this->claim($record)) {
            return;
        }
        try {
            if ($event['paid'] && $event['order_id']) {
                $this->fulfillment->fulfill($event['order_id'], (string) $event['payment_id'], $provider, $decoded);
            }
            $record->update(['processing_status' => 'processed', 'processed_at' => now()]);
        } catch (\Throwable $e) {
            $record->update(['processing_status' => 'failed', 'error_message' => $e->getMessage(), 'retry_count' => $record->retry_count + 1]);
            throw $e;
        }
    }

    /** Exactly one caller wins the row; stale 'processing' claims are reclaimable. */
    private function claim(PaymentWebhookEvent $record): bool
    {
        return PaymentWebhookEvent::whereKey($record->id)->where(fn ($q) => $q->whereIn('processing_status', ['pending', 'failed'])->orWhere(fn ($stale) => $stale->where('processing_status', 'processing')->where('updated_at', '<=', now()->subMinutes(5))))->update(['processing_status' => 'processing']) > 0;
    }
}

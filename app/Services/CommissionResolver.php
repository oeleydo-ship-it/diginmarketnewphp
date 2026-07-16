<?php

namespace App\Services;

use App\Models\CommissionRule;
use App\Models\Product;

class CommissionResolver
{
    public function __construct(private ?SubscriptionService $subscriptions = null) {}

    public function resolve(Product $product, float $gross): array
    {
        // Explicit admin rules win first (a product- or seller-specific deal is deliberate). If none
        // apply, a seller's active subscription commission override takes precedence over the broad
        // category/global rules — that reduced rate is the whole point of paying for a plan.
        $rule = $this->matchRule('product', $product->id) ?? $this->matchRule('seller', $product->seller_id);
        $planRate = $rule ? null : $this->subscriptionRate($product);
        if ($rule === null && $planRate === null) {
            $rule = $this->matchRule('category', $product->category_id) ?? $this->matchRule('global', null);
        }
        $rate = (float) ($rule?->rate ?? $planRate ?? config('marketplace.default_commission_rate', 20));
        $fixed = (float) ($rule?->fixed_fee ?? 0);
        $commission = min($gross, round(($gross * $rate / 100) + $fixed, 2));

        return ['rate' => $rate, 'fixed_fee' => $fixed, 'commission' => $commission, 'seller_earning' => round($gross - $commission, 2), 'rule_id' => $rule?->id];
    }

    private function matchRule(string $type, ?int $id): ?CommissionRule
    {
        $query = CommissionRule::where('scope_type', $type)->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
        $id === null ? $query->whereNull('scope_id') : $query->where('scope_id', $id);

        return $query->orderByDesc('priority')->first();
    }

    /** The seller's active plan commission override, if any (null means no override). */
    private function subscriptionRate(Product $product): ?float
    {
        $subscriptions = $this->subscriptions ?? app(SubscriptionService::class);
        $seller = $product->relationLoaded('seller') ? $product->seller : $product->seller()->first();
        if ($seller === null) {
            return null;
        }
        $rate = $subscriptions->activeFor($seller)?->commission_rate;

        return $rate === null ? null : (float) $rate;
    }
}

<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SellerSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Start a subscription. Free plans go live immediately; paid plans are created pending until
     * payment is confirmed (mirrors the bank-transfer flow), so the platform never gives a paid
     * tier away for free. Terms are snapshotted from the plan at purchase time.
     */
    public function subscribe(User $seller, SubscriptionPlan $plan): SellerSubscription
    {
        return DB::transaction(function () use ($seller, $plan) {
            $subscription = SellerSubscription::create([
                'user_id' => $seller->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'pending',
                'price' => $plan->price,
                'billing_period' => $plan->billing_period,
                'commission_rate' => $plan->commission_rate,
                'listing_limit' => $plan->listing_limit,
            ]);
            if ($plan->isFree()) {
                $this->activate($subscription, 'free-plan');
            }

            return $subscription->fresh();
        });
    }

    /**
     * Confirm and switch on a subscription, ending any other current subscription for the seller so
     * only one is ever active. ends_at is computed from the billing period; lifetime never expires.
     */
    public function activate(SellerSubscription $subscription, ?string $paymentReference = null): SellerSubscription
    {
        return DB::transaction(function () use ($subscription, $paymentReference) {
            SellerSubscription::where('user_id', $subscription->user_id)
                ->where('id', '!=', $subscription->id)
                ->where('status', 'active')
                ->update(['status' => 'expired', 'ends_at' => now()]);
            $subscription->update([
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => $this->periodEnd($subscription->billing_period),
                'payment_reference' => $paymentReference,
            ]);
            AuditLog::create(['user_id' => $subscription->user_id, 'action' => 'subscription.activated', 'entity_type' => SellerSubscription::class, 'entity_id' => $subscription->id, 'new_values' => ['plan' => $subscription->subscription_plan_id, 'reference' => $paymentReference], 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);

            return $subscription->fresh();
        });
    }

    public function cancel(SellerSubscription $subscription): SellerSubscription
    {
        $subscription->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return $subscription->fresh();
    }

    /** Scheduler hook: retire subscriptions whose fixed term has elapsed. Returns the count expired. */
    public function expireDue(): int
    {
        return SellerSubscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->update(['status' => 'expired']);
    }

    public function activeFor(User $seller): ?SellerSubscription
    {
        return SellerSubscription::where('user_id', $seller->id)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->latest('starts_at')
            ->first();
    }

    /** Null means unlimited: either no active plan (free tier) or a plan with no cap. */
    public function listingLimitFor(User $seller): ?int
    {
        return $this->activeFor($seller)?->listing_limit;
    }

    /** Products that count against the cap — anything not a draft or rejected listing. */
    public function activeListingCount(User $seller): int
    {
        return $seller->products()
            ->whereNotIn('status', ['draft', 'rejected'])
            ->count();
    }

    public function canCreateListing(User $seller): bool
    {
        $limit = $this->listingLimitFor($seller);

        return $limit === null || $this->activeListingCount($seller) < $limit;
    }

    private function periodEnd(string $period): ?Carbon
    {
        return match ($period) {
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            default => null, // lifetime
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerSubscription extends Model
{
    protected $fillable = ['user_id', 'subscription_plan_id', 'status', 'price', 'billing_period', 'commission_rate', 'listing_limit', 'starts_at', 'ends_at', 'payment_reference', 'cancelled_at'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'commission_rate' => 'decimal:2', 'listing_limit' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Active and either lifetime (no end) or not yet past its end date. */
    public function isCurrentlyActive(): bool
    {
        return $this->status === 'active' && ($this->ends_at === null || $this->ends_at->isFuture());
    }
}

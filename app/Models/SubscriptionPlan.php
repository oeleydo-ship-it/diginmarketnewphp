<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = ['name', 'slug', 'price', 'billing_period', 'commission_rate', 'listing_limit', 'features', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'commission_rate' => 'decimal:2', 'listing_limit' => 'integer', 'features' => 'array', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SellerSubscription::class);
    }

    public function isFree(): bool
    {
        return (float) $this->price <= 0;
    }
}

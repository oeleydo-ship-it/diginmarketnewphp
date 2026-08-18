<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Cart extends Model
{
    protected $fillable = ['user_id', 'currency', 'coupon_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /** Drop line items for these products after a paid order is fulfilled. */
    public function removeProducts(iterable $productIds): void
    {
        $ids = Collection::wrap($productIds)->filter()->unique()->values()->all();
        if ($ids === []) {
            return;
        }
        $this->items()->whereIn('product_id', $ids)->delete();
        $this->forgetCouponIfEmpty();
    }

    private function forgetCouponIfEmpty(): void
    {
        if ($this->coupon_id && $this->items()->doesntExist()) {
            $this->update(['coupon_id' => null]);
        }
    }
}

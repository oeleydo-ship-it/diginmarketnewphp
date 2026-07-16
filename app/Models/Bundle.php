<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bundle extends Model
{
    protected $fillable = ['seller_id', 'title', 'slug', 'description', 'price', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** Purchasable only while every included product is still published. */
    public function isPurchasable(): bool
    {
        return $this->is_active && $this->products()->count() >= 2 && $this->products()->where('status', '!=', 'published')->doesntExist();
    }

    /** Query form of isPurchasable() for listings, so pages filter without per-bundle queries. */
    public function scopePurchasable($query)
    {
        return $query->where('is_active', true)
            ->has('products', '>=', 2)
            ->whereDoesntHave('products', fn ($q) => $q->where('status', '!=', 'published'));
    }

    /** Sum of the products' individual regular prices, for showing the saving. */
    public function compareAtPrice(): float
    {
        return (float) $this->products()->sum('regular_price');
    }
}

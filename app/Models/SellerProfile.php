<?php

namespace App\Models;

use App\Enums\SellerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerProfile extends Model
{
    protected $fillable = ['user_id', 'full_name', 'display_name', 'username', 'country', 'address', 'city', 'postal_code', 'phone', 'biography', 'business_name', 'website', 'status', 'is_featured', 'rejection_reason', 'reviewed_at'];

    protected function casts(): array
    {
        return ['status' => SellerStatus::class, 'is_featured' => 'boolean', 'reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

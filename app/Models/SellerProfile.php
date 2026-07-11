<?php

namespace App\Models;

use App\Enums\SellerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerProfile extends Model
{
    protected $fillable = ['user_id', 'display_name', 'username', 'country', 'phone', 'biography', 'business_name', 'website', 'status', 'rejection_reason', 'reviewed_at'];

    protected function casts(): array
    {
        return ['status' => SellerStatus::class, 'reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

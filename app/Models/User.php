<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** 2FA is in force only once the user has confirmed enrolment with a valid code. */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /** Consume a single-use recovery code; returns false if it was not among the unused set. */
    public function useRecoveryCode(string $code): bool
    {
        $codes = (array) $this->two_factor_recovery_codes;
        $remaining = array_values(array_filter($codes, fn ($stored) => ! hash_equals($stored, $code)));
        if (count($remaining) === count($codes)) {
            return false;
        }
        $this->forceFill(['two_factor_recovery_codes' => $remaining])->save();

        return true;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function sellerProfile(): HasOne
    {
        return $this->hasOne(SellerProfile::class);
    }

    public function affiliateProfile(): HasOne
    {
        return $this->hasOne(AffiliateProfile::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function wishlist(): HasOne
    {
        return $this->hasOne(Wishlist::class);
    }

    public function followedSellers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'seller_followers', 'follower_id', 'seller_id')->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'seller_followers', 'seller_id', 'follower_id')->withTimestamps();
    }

    public function cart(): HasOne { return $this->hasOne(Cart::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function licenses(): HasMany { return $this->hasMany(License::class); }
    public function sellerWallets(): HasMany { return $this->hasMany(SellerWallet::class, 'seller_id'); }
    public function withdrawals(): HasMany { return $this->hasMany(WithdrawalRequest::class, 'seller_id'); }
    public function stripeConnectedAccount(): HasOne { return $this->hasOne(StripeConnectedAccount::class, 'seller_id'); }
    public function reviews(): HasMany { return $this->hasMany(Review::class); }
    public function supportTickets(): HasMany { return $this->hasMany(SupportTicket::class); }
    public function refundRequests(): HasMany { return $this->hasMany(RefundRequest::class); }
    public function notificationPreference(): HasOne { return $this->hasOne(NotificationPreference::class); }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

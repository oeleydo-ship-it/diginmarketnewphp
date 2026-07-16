<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Which payout methods the marketplace offers sellers. Admin toggles live in
 * Settings → Payout Methods; everything defaults to on so upgrades change nothing.
 */
class PayoutMethods
{
    public const LABELS = ['stripe' => 'Stripe Connect', 'paypal' => 'PayPal', 'bank' => 'Bank transfer'];

    /** @return list<string> */
    public static function enabled(): array
    {
        return array_values(array_filter(array_keys(self::LABELS), fn (string $key) => self::isEnabled($key)));
    }

    public static function isEnabled(string $key): bool
    {
        return array_key_exists($key, self::LABELS) && Setting::enabled('payouts.'.$key.'.enabled', true);
    }
}

<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Applies admin-managed database settings over the env-derived config at boot.
 * Extracted from AppServiceProvider so tests can re-apply after seeding settings.
 */
class DatabaseSettings
{
    public static function apply(): void
    {
        $settings = rescue(fn () => Setting::query()->get()->keyBy('key'), collect(), false);
        if ($settings->isEmpty()) {
            return;
        }
        $value = function (string $key) use ($settings): ?string {
            $setting = $settings->get($key);
            if (! $setting) {
                return null;
            }
            $raw = $setting->is_encrypted ? rescue(fn () => decrypt((string) $setting->value), null, false) : $setting->value;
            return ($raw === null || $raw === '') ? null : (string) $raw;
        };

        if ($name = $value('marketplace.name')) config(['app.name' => $name]);
        if ($logo = $value('branding.logo_path')) config(['marketplace.logo_path' => $logo]);
        if ($tagline = $value('marketplace.tagline')) config(['marketplace.tagline' => $tagline]);
        foreach (['seo.meta_title', 'seo.meta_description', 'seo.meta_keywords'] as $key) {
            if ($v = $value($key)) config([str_replace('seo.', 'marketplace.seo_', $key) => $v]);
        }
        foreach (['host' => 'mail.mailers.smtp.host', 'port' => 'mail.mailers.smtp.port', 'username' => 'mail.mailers.smtp.username', 'password' => 'mail.mailers.smtp.password'] as $key => $config) {
            if ($v = $value('mail.'.$key)) config([$config => $key === 'port' ? (int) $v : $v]);
        }
        if ($value('mail.encryption') === 'ssl') config(['mail.mailers.smtp.scheme' => 'smtps']);
        if ($from = $value('mail.from_address')) config(['mail.from.address' => $from]);
        if ($fromName = $value('mail.from_name')) config(['mail.from.name' => $fromName]);
        if ($pk = $value('payments.stripe_publishable_key')) config(['services.stripe.key' => $pk]);
        if ($sk = $value('payments.stripe_secret_key')) config(['services.stripe.secret' => $sk]);
        if ($ws = $value('payments.stripe_webhook_secret')) config(['services.stripe.webhook_secret' => $ws]);

        // Commerce tuning: numeric marketplace levers managed from the admin panel.
        foreach (['commerce.default_commission_rate' => 'marketplace.default_commission_rate', 'commerce.affiliate_commission_rate' => 'marketplace.affiliate_commission_rate', 'commerce.withdrawal_fee_rate' => 'marketplace.withdrawal_fee_rate'] as $key => $config) {
            if (($v = $value($key)) !== null && is_numeric($v)) config([$config => (float) $v]);
        }
        if (($v = $value('commerce.earnings_clearance_days')) !== null && is_numeric($v)) config(['marketplace.earnings_clearance_days' => (int) $v]);
        if (($v = $value('commerce.minimum_withdrawal')) !== null && is_numeric($v)) config(['marketplace.minimum_withdrawal' => (float) $v]);
        if ($currency = $value('marketplace.currency')) config(['marketplace.currency' => strtoupper($currency)]);
    }
}

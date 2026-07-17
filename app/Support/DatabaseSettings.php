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
        // Gateway credentials: admin-managed values win over env so a marketplace can be
        // reconfigured from the panel. Setting key => config key.
        foreach ([
            'payments.stripe_publishable_key' => 'services.stripe.key',
            'payments.stripe_secret_key' => 'services.stripe.secret',
            'payments.stripe_webhook_secret' => 'services.stripe.webhook_secret',
            'payments.paypal_client_id' => 'services.paypal.client_id',
            'payments.paypal_secret' => 'services.paypal.secret',
            'payments.paypal_mode' => 'services.paypal.mode',
            'payments.paypal_webhook_id' => 'services.paypal.webhook_id',
            'payments.razorpay_key' => 'services.razorpay.key',
            'payments.razorpay_secret' => 'services.razorpay.secret',
            'payments.razorpay_webhook_secret' => 'services.razorpay.webhook_secret',
            'payments.paystack_public_key' => 'services.paystack.public_key',
            'payments.paystack_secret' => 'services.paystack.secret',
            'payments.bank_transfer_instructions' => 'services.bank_transfer.instructions',
            'auth.google_client_id' => 'services.google.client_id',
            'auth.google_client_secret' => 'services.google.secret',
            'auth.facebook_client_id' => 'services.facebook.client_id',
            'auth.facebook_client_secret' => 'services.facebook.secret',
            'integrations.tawk_property_id' => 'services.tawk.property_id',
            'integrations.tawk_widget_id' => 'services.tawk.widget_id',
        ] as $key => $config) {
            if ($v = $value($key)) config([$config => $v]);
        }

        // Commerce tuning: numeric marketplace levers managed from the admin panel.
        if ($v = $value('commerce.tax_label')) config(['marketplace.tax_label' => $v]);
        foreach (['commerce.default_commission_rate' => 'marketplace.default_commission_rate', 'commerce.affiliate_commission_rate' => 'marketplace.affiliate_commission_rate', 'commerce.withdrawal_fee_rate' => 'marketplace.withdrawal_fee_rate', 'commerce.tax_rate' => 'marketplace.tax_rate'] as $key => $config) {
            if (($v = $value($key)) !== null && is_numeric($v)) config([$config => (float) $v]);
        }
        if (($v = $value('commerce.earnings_clearance_days')) !== null && is_numeric($v)) config(['marketplace.earnings_clearance_days' => (int) $v]);
        if (($v = $value('commerce.minimum_withdrawal')) !== null && is_numeric($v)) config(['marketplace.minimum_withdrawal' => (float) $v]);
        if ($currency = $value('marketplace.currency')) config(['marketplace.currency' => strtoupper($currency)]);
    }
}

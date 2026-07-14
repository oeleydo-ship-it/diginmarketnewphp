<?php
namespace App\Providers;
use App\Contracts\CheckoutGateway;
use App\Services\StripeCheckoutGateway;
use App\Contracts\RefundGateway;
use App\Services\StripeRefundGateway;
use App\Contracts\PayoutGateway;
use App\Services\StripePayoutGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void { $this->app->bind(CheckoutGateway::class, StripeCheckoutGateway::class); $this->app->bind(RefundGateway::class, StripeRefundGateway::class); $this->app->bind(PayoutGateway::class, StripePayoutGateway::class); }
    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('license-api', fn (Request $request) => Limit::perMinute(30)->by(($request->input('license_key') ?: 'anonymous').'|'.$request->ip()));
        Model::preventLazyLoading(! $this->app->isProduction());
        $this->applyDatabaseSettings();
    }

    private function applyDatabaseSettings(): void
    {
        $settings = rescue(fn () => \App\Models\Setting::query()->get()->keyBy('key'), collect(), false);
        if ($settings->isEmpty()) return;
        $value = function (string $key) use ($settings): ?string {
            $setting = $settings->get($key);
            if (! $setting) return null;
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
    }
}

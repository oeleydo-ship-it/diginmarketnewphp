<?php

namespace App\Providers;

use App\Contracts\FileScanner;
use App\Contracts\PayoutGateway;
use App\Contracts\RefundGateway;
use App\Services\ProviderRefundGateway;
use App\Services\Scanners\BasicArchiveScanner;
use App\Services\Scanners\ClamAvScanner;
use App\Services\StripePayoutGateway;
use App\Support\DatabaseSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Checkout drivers are resolved per order by PaymentGatewayManager, so only the single-provider gateways bind here. */
    public function register(): void
    {
        $this->app->bind(RefundGateway::class, ProviderRefundGateway::class);
        $this->app->bind(PayoutGateway::class, StripePayoutGateway::class);
        // ClamAV when a binary is configured; otherwise the archive-integrity fallback.
        $this->app->bind(FileScanner::class, fn () => config('marketplace.clamav_path')
            ? new ClamAvScanner
            : new BasicArchiveScanner);
    }

    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('license-api', fn (Request $request) => Limit::perMinute(30)->by(($request->input('license_key') ?: 'anonymous').'|'.$request->ip()));
        Model::preventLazyLoading(! $this->app->isProduction());
        DatabaseSettings::apply();
    }
}

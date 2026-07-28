<?php

namespace App\Providers;

use App\Contracts\FileScanner;
use App\Contracts\PayoutGateway;
use App\Contracts\RefundGateway;
use App\Http\Middleware\EnsureInstalled;
use App\Services\ProviderRefundGateway;
use App\Services\Scanners\BasicArchiveScanner;
use App\Services\Scanners\ClamAvScanner;
use App\Services\StripePayoutGateway;
use App\Support\DatabaseSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Checkout drivers are resolved per order by PaymentGatewayManager, so only the single-provider gateways bind here. */
    public function register(): void
    {
        $this->bootstrapWithoutDatabase();
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
        $this->ignoreStaleViteHotFile();
    }

    /**
     * `npm run dev` drops public/hot, and a release zip built from a working copy carries
     * it along. Its presence makes @vite point every stylesheet and script at the developer's
     * own 127.0.0.1:5173, so the deployed site renders as bare unstyled HTML. Outside local
     * development the compiled manifest is the only correct source, so look for the hot file
     * somewhere it will never exist.
     */
    private function ignoreStaleViteHotFile(): void
    {
        if (! $this->app->isLocal()) {
            Vite::useHotFile(storage_path('framework/vite.hot'));
        }
    }

    /**
     * Until the installer has run, the database credentials in .env are whatever the
     * host shipped — usually wrong. Anything that touches the database on a plain page
     * view (sessions, cache, queue) would then throw before the installer can render,
     * so those drivers fall back to the filesystem until the lock file exists.
     */
    private function bootstrapWithoutDatabase(): void
    {
        if ($this->app->runningUnitTests() || EnsureInstalled::installed()) {
            return;
        }

        config([
            'session.driver' => 'file',
            'cache.default' => 'file',
            'queue.default' => 'sync',
        ]);
    }
}

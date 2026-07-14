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
        \App\Support\DatabaseSettings::apply();
    }
}

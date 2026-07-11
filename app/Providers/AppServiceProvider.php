<?php
namespace App\Providers;
use App\Contracts\CheckoutGateway;
use App\Services\StripeCheckoutGateway;
use App\Contracts\RefundGateway;
use App\Services\StripeRefundGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void { $this->app->bind(CheckoutGateway::class, StripeCheckoutGateway::class); $this->app->bind(RefundGateway::class, StripeRefundGateway::class); }
    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}

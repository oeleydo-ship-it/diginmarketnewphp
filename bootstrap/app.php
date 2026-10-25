<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth']]
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: ['stripe/webhook', 'payments/*/webhook']);
        // Set by plain JS in the consent banner, so it cannot be an encrypted Laravel cookie.
        $middleware->encryptCookies(except: ['dm_cookie_consent']);
        $middleware->web(prepend: [\App\Http\Middleware\EnsureInstalled::class]);
        $middleware->web(append: [\App\Http\Middleware\SetLocale::class, \App\Http\Middleware\HandleImpersonationExpiry::class, \App\Http\Middleware\SecurityHeaders::class, \App\Http\Middleware\TrackAffiliateReferral::class]);
        $middleware->alias([
            'role' => \App\Http\Middleware\RequireRole::class,
            'verified' => \App\Http\Middleware\EnsureEmailVerificationIsEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // A body over post_max_size arrives empty (no session, no CSRF token), so the
        // form can't be re-validated; send the seller back to it with a readable notice.
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return null;
            }
            $back = (string) $request->headers->get('referer');
            if (! str_starts_with($back, url('/').'/')) {
                $back = url('/');
            }

            return redirect()->to($back.(str_contains($back, '?') ? '&' : '?').'upload_too_large=1');
        });
    })->create();

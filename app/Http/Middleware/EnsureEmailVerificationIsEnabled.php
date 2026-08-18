<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerificationIsEnabled
{
    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response
    {
        if (! Setting::enabled('features.email_verification', true)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || ! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Your email address is not verified.');
        }

        return redirect()->guest(URL::route($redirectToRoute ?: 'verification.notice'));
    }
}

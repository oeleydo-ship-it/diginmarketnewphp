<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // The Vite dev server serves assets and HMR websockets from its own origin locally,
        // and may bind to IPv6 loopback ([::1]) depending on the OS resolver.
        $vite = app()->isLocal() ? ' http://localhost:5173 http://127.0.0.1:5173 http://[::1]:5173' : '';
        $ws = app()->isLocal() ? ' ws://localhost:5173 ws://127.0.0.1:5173 ws://[::1]:5173' : '';

        // Google Fonts serves the marketplace typefaces (Inter, Geist, JetBrains Mono, Material Symbols).
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'{$vite}; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com{$vite}; img-src 'self' data: https:; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self'{$vite}{$ws}; frame-ancestors 'none'; form-action 'self' https://checkout.stripe.com; base-uri 'self'; object-src 'none'");
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}

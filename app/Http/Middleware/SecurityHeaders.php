<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Inline theme bootstrap: applies the stored (or OS-preferred) theme before first
     * paint to avoid a light flash. Rendered verbatim by the layout and allowed through
     * the CSP by hash — keep both in sync by only editing this constant.
     */
    public const THEME_BOOTSTRAP = "(function(){try{var t=localStorage.getItem('dm-theme');if(t==='dark'||(!t&&matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}}catch(e){}})()";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $themeHash = "'sha256-".base64_encode(hash('sha256', self::THEME_BOOTSTRAP, true))."'";

        // The Vite dev server serves assets and HMR websockets from its own origin locally,
        // and may bind to IPv6 loopback ([::1]) depending on the OS resolver.
        $vite = app()->isLocal() ? ' http://localhost:5173 http://127.0.0.1:5173 http://[::1]:5173' : '';
        $ws = app()->isLocal() ? ' ws://localhost:5173 ws://127.0.0.1:5173 ws://[::1]:5173' : '';

        // Tawk.to live chat loads scripts, sockets and an iframe from *.tawk.to — allowed
        // only while an admin has actually configured a property id.
        $tawkScript = config('services.tawk.property_id') ? ' https://embed.tawk.to https://*.tawk.to' : '';
        $tawkConnect = config('services.tawk.property_id') ? ' https://*.tawk.to wss://*.tawk.to' : '';
        $tawkFrame = config('services.tawk.property_id') ? ' https://tawk.to https://*.tawk.to' : '';

        // Google Fonts serves the marketplace typefaces (Inter, Geist, JetBrains Mono, Material Symbols).
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' {$themeHash}{$vite}{$tawkScript}; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com{$vite}{$tawkScript}; img-src 'self' data: https:; font-src 'self' data: https://fonts.gstatic.com{$tawkScript}; connect-src 'self'{$vite}{$ws}{$tawkConnect}; frame-src https://www.youtube-nocookie.com https://player.vimeo.com{$tawkFrame}; frame-ancestors 'none'; form-action 'self' https://checkout.stripe.com; base-uri 'self'; object-src 'none'");
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

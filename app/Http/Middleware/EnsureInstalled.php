<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public static function lockPath(): string
    {
        return config('marketplace.install_lock') ?: storage_path('app/installed.lock');
    }

    public static function installed(): bool
    {
        return file_exists(static::lockPath());
    }

    public function handle(Request $request, Closure $next): Response
    {
        // The test suite runs against a migrated in-memory database with no lock file;
        // only installer-specific tests opt back in via marketplace.enforce_installer.
        if (app()->runningUnitTests() && ! config('marketplace.enforce_installer')) {
            return $next($request);
        }

        if (static::installed()) {
            if ($request->routeIs('install.*')) {
                return redirect()->route('home');
            }
            return $next($request);
        }

        if ($request->routeIs('install.*')) {
            return $next($request);
        }

        return redirect()->route('install.show');
    }
}

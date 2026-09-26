<?php

namespace App\Http\Middleware;

use Closure;
use App\Support\EnvWriter;
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

        $this->prepareAppKey();

        if ($request->routeIs('install.*')) {
            return $next($request);
        }

        return redirect()->route('install.show');
    }

    private function prepareAppKey(): void
    {
        if (config('app.key')) {
            return;
        }

        $envPath = config('marketplace.env_path') ?: base_path('.env');
        if (! is_file($envPath)) {
            $example = base_path('.env.example');
            if (! is_file($example) || ! copy($example, $envPath)) {
                abort(503, 'Create a writable .env file from .env.example to start installation.');
            }
            app(EnvWriter::class)->set(['APP_ENV' => 'production', 'APP_DEBUG' => 'false']);
        }

        $key = 'base64:'.base64_encode(random_bytes(32));
        app(EnvWriter::class)->set(['APP_KEY' => $key]);
        config(['app.key' => $key]);
        if (app()->configurationIsCached()) {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
        }
    }
}

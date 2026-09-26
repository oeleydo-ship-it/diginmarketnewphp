<?php

namespace App\Http\Middleware;

use Closure;
use App\Support\EnvWriter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            if ($request->routeIs('install.*', 'admin-setup.*')) {
                return redirect()->route('home');
            }
            return $next($request);
        }

        $this->prepareAppKey();

        if ($this->useAdministratorSetup()) {
            // A Git deployment may replace storage while keeping its database. An
            // existing administrator means setup is already complete in that case.
            if ($this->administratorExists()) {
                $this->writeLock();
                return $request->routeIs('install.*', 'admin-setup.*')
                    ? redirect()->route('home')
                    : $next($request);
            }

            return $request->routeIs('admin-setup.*')
                ? $next($request)
                : redirect()->route('admin-setup.show');
        }

        if ($request->routeIs('install.*')) {
            return $next($request);
        }

        return redirect()->route('install.show');
    }

    private function useAdministratorSetup(): bool
    {
        $mode = config('marketplace.setup_mode', 'admin');
        if ($mode === 'admin') {
            return true;
        }
        if ($mode === 'manual') {
            return false;
        }

        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function administratorExists(): bool
    {
        try {
            return Schema::hasTable('users') && Schema::hasTable('roles')
                && User::whereHas('roles', fn ($query) => $query->where('slug', 'administrator'))->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    public static function writeLock(?string $email = null): void
    {
        file_put_contents(static::lockPath(), json_encode([
            'installed_at' => now()->toIso8601String(),
            'admin' => $email,
            'version' => app()->version(),
        ]));
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

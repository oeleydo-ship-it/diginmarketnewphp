<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureInstalled;
use App\Models\LicenseType;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\EnvWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstallController extends Controller
{
    public function show(): View
    {
        // Database sessions need the schema before the first response can be stored.
        rescue(function () {
            if (! Schema::hasTable('sessions')) {
                Artisan::call('migrate', ['--force' => true]);
            }
        }, report: false);

        return view('install.show', ['requirements' => $this->requirements()]);
    }

    /**
     * Step 1: configure the database. The connection is proven with a live PDO handshake
     * BEFORE anything is written to .env, so a typo never bricks the app mid-install.
     */
    public function database(): RedirectResponse
    {
        abort_if(EnsureInstalled::installed(), 403, 'The marketplace is already installed.');
        $data = request()->validate([
            'connection' => ['required', 'in:mysql,sqlite'],
            'host' => ['required_if:connection,mysql', 'nullable', 'string', 'max:255'],
            'port' => ['required_if:connection,mysql', 'nullable', 'integer', 'between:1,65535'],
            'database' => ['required_if:connection,mysql', 'nullable', 'string', 'max:64'],
            'username' => ['required_if:connection,mysql', 'nullable', 'string', 'max:64'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);
        if ($data['connection'] === 'mysql') {
            try {
                new \PDO('mysql:host='.$data['host'].';port='.$data['port'].';dbname='.$data['database'], $data['username'], $data['password'] ?? '', [\PDO::ATTR_TIMEOUT => 4, \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            } catch (\PDOException $e) {
                return back()->withInput()->withErrors(['database' => 'Could not connect to MySQL: '.$e->getMessage()]);
            }
            app(EnvWriter::class)->set(['DB_CONNECTION' => 'mysql', 'DB_HOST' => $data['host'], 'DB_PORT' => (string) $data['port'], 'DB_DATABASE' => $data['database'], 'DB_USERNAME' => $data['username'], 'DB_PASSWORD' => $data['password'] ?? '']);
        } else {
            $file = database_path('database.sqlite');
            if (! is_file($file)) {
                touch($file);
            }
            app(EnvWriter::class)->set(['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $file]);
        }
        // A cached config would keep serving the old connection; clear it so the next request
        // (which runs migrations via show()) reads the fresh .env.
        rescue(fn () => Artisan::call('config:clear'), report: false);

        return redirect()->route('install.show')->with('db_status', 'Database connection saved. Create your administrator account below.');
    }

    public function store(): RedirectResponse
    {
        abort_if(EnsureInstalled::installed(), 403, 'The marketplace is already installed.');
        abort_if(collect($this->requirements())->contains(fn ($ok) => ! $ok), 422, 'Server requirements are not met.');
        $data = request()->validate(['site_name' => ['required', 'string', 'max:100'], 'admin_name' => ['required', 'string', 'max:100'], 'admin_email' => ['required', 'email', 'max:255'], 'admin_password' => ['required', 'string', 'min:10', 'confirmed']]);
        Artisan::call('migrate', ['--force' => true]);
        foreach ([['Administrator', 'administrator', 'Full marketplace operations'], ['Seller', 'seller', 'Product publishing and sales'], ['Customer', 'customer', 'Purchasing and licensing']] as [$name,$slug,$description]) {
            Role::updateOrCreate(['slug' => $slug], ['name' => $name, 'description' => $description]);
        }
        LicenseType::updateOrCreate(['slug' => 'regular'], ['name' => 'Regular License', 'description' => 'Use in one end product where end users are not charged.', 'allows_paid_end_product' => false]);
        LicenseType::updateOrCreate(['slug' => 'extended'], ['name' => 'Extended License', 'description' => 'Use in one end product where end users may be charged.', 'allows_paid_end_product' => true]);
        Setting::updateOrCreate(['key' => 'marketplace.name'], ['group' => 'general', 'value' => $data['site_name'], 'is_public' => true]);
        // Stamp the shipped baseline so the System Health page reports a real version from day one.
        Setting::put('system.version', (string) config('marketplace.version', '1.0.0'), 'system');
        $admin = User::updateOrCreate(['email' => $data['admin_email']], ['name' => $data['admin_name'], 'password' => bcrypt($data['admin_password']), 'status' => 'active', 'email_verified_at' => now()]);
        $admin->roles()->syncWithoutDetaching([Role::where('slug', 'administrator')->firstOrFail()->id]);
        file_put_contents(EnsureInstalled::lockPath(), json_encode(['installed_at' => now()->toIso8601String(), 'admin' => $admin->email, 'version' => app()->version()]));

        return redirect()->route('login')->with('status', 'Installation complete. Sign in with your administrator account.');
    }

    private function requirements(): array
    {
        $database = true;
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $database = false;
        }

        return [
            'PHP 8.2 or newer' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'OpenSSL extension' => extension_loaded('openssl'),
            'PDO extension' => extension_loaded('pdo'),
            'Mbstring extension' => extension_loaded('mbstring'),
            'Curl extension' => extension_loaded('curl'),
            'Zip extension' => extension_loaded('zip'),
            'Application key set' => (bool) config('app.key'),
            'storage/ directory writable' => is_writable(storage_path()),
            'bootstrap/cache writable' => is_writable(base_path('bootstrap/cache')),
            'Database connection' => $database,
        ];
    }
}

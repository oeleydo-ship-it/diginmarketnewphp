<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureInstalled;
use App\Models\LicenseType;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

class AdministratorSetupController extends Controller
{
    public function show(): View
    {
        Artisan::call('migrate', ['--force' => true]);
        abort_if(User::query()->exists(), 409, 'This database already contains users. Restore the installation lock or use a fresh database.');

        return view('install.administrator');
    }

    public function store(): RedirectResponse
    {
        $data = request()->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        Artisan::call('migrate', ['--force' => true]);
        abort_if(User::query()->exists(), 409, 'Administrator setup has already been used for this database.');

        foreach ([['Administrator', 'administrator', 'Full marketplace operations'], ['Seller', 'seller', 'Product publishing and sales'], ['Customer', 'customer', 'Purchasing and licensing']] as [$name, $slug, $description]) {
            Role::updateOrCreate(['slug' => $slug], ['name' => $name, 'description' => $description]);
        }
        LicenseType::updateOrCreate(['slug' => 'regular'], ['name' => 'Regular License', 'description' => 'Use in one end product where end users are not charged.', 'allows_paid_end_product' => false]);
        LicenseType::updateOrCreate(['slug' => 'extended'], ['name' => 'Extended License', 'description' => 'Use in one end product where end users may be charged.', 'allows_paid_end_product' => true]);
        Setting::put('system.version', (string) config('marketplace.version', '1.0.0'), 'system');
        Setting::updateOrCreate(['key' => 'marketplace.name'], ['group' => 'general', 'value' => config('app.name', 'DiginMarket'), 'is_public' => true]);

        $admin = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $admin->roles()->syncWithoutDetaching([Role::where('slug', 'administrator')->firstOrFail()->id]);
        EnsureInstalled::writeLock($admin->email);

        return redirect()->route('login')->with('status', 'Administrator account created. Sign in to continue.');
    }
}

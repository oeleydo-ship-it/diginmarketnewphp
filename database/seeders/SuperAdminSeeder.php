<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPERADMIN_EMAIL');
        $password = env('SUPERADMIN_PASSWORD');
        if (! $email || ! $password) {
            $this->command?->info('Superadmin seeding skipped; create the account through /install.');
            return;
        }

        $role = Role::updateOrCreate(
            ['slug' => 'administrator'],
            ['name' => 'Administrator', 'description' => 'Full marketplace operations'],
        );

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('SUPERADMIN_NAME', 'DiginMarket Super Admin'),
                'password' => $password,
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );

        $admin->roles()->syncWithoutDetaching([$role->id]);

        $this->command?->info("Super admin ready: {$admin->email}");
    }
}

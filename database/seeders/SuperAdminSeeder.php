<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::updateOrCreate(
            ['slug' => 'administrator'],
            ['name' => 'Administrator', 'description' => 'Full marketplace operations'],
        );

        $admin = User::updateOrCreate(
            ['email' => env('SUPERADMIN_EMAIL', 'superadmin@diginmarket.test')],
            [
                'name' => env('SUPERADMIN_NAME', 'DiginMarket Super Admin'),
                'password' => env('SUPERADMIN_PASSWORD', 'Admin@12345'),
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );

        $admin->roles()->syncWithoutDetaching([$role->id]);

        $this->command?->info("Super admin ready: {$admin->email}");
    }
}

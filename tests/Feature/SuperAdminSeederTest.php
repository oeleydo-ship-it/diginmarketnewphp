<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_an_active_verified_administrator_idempotently(): void
    {
        \Illuminate\Support\Env::getRepository()->set('SUPERADMIN_EMAIL', 'superadmin@diginmarket.test');
        \Illuminate\Support\Env::getRepository()->set('SUPERADMIN_PASSWORD', 'Admin@12345');
        $this->seed(SuperAdminSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $admin = User::where('email', 'superadmin@diginmarket.test')->sole();

        $this->assertSame('active', $admin->status);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue($admin->hasRole('administrator'));
        $this->assertTrue(Hash::check('Admin@12345', $admin->password));
        $this->assertDatabaseCount('users', 1);
    }
}

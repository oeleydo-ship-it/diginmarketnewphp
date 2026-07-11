<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_open_administrator_dashboard(): void
    {
        $role = Role::create(['name' => 'Customer', 'slug' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_administrator_can_open_administrator_dashboard(): void
    {
        $role = Role::create(['name' => 'Administrator', 'slug' => 'administrator']);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $this->actingAs($user)->get('/admin')->assertOk();
    }
}

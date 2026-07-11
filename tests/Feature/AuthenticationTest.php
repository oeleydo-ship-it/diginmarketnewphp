<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_receives_customer_role(): void
    {
        Role::create(['name' => 'Customer', 'slug' => 'customer']);
        $response = $this->post('/register', ['name' => 'Buyer', 'email' => 'buyer@example.com', 'password' => 'StrongPass1', 'password_confirmation' => 'StrongPass1']);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->hasRole('customer'));
    }
}

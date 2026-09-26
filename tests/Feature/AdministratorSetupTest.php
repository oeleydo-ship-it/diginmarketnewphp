<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministratorSetupTest extends TestCase
{
    use RefreshDatabase;

    private string $lock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lock = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dm-admin-setup-'.uniqid().'.lock';
        config(['marketplace.install_lock' => $this->lock, 'marketplace.enforce_installer' => true, 'marketplace.setup_mode' => 'admin']);
    }

    protected function tearDown(): void
    {
        @unlink($this->lock);
        parent::tearDown();
    }

    public function test_ready_database_shows_only_administrator_setup(): void
    {
        $this->get('/')->assertRedirect(route('admin-setup.show'));
        $this->get('/install')->assertRedirect(route('admin-setup.show'));
        $this->get('/setup-admin')->assertOk()
            ->assertSee('Create your administrator account')
            ->assertDontSee('Database driver')
            ->assertDontSee('Server requirements');
    }

    public function test_administrator_setup_creates_first_account_and_cannot_run_again(): void
    {
        $this->post('/setup-admin', [
            'name' => 'Site Owner',
            'email' => 'owner@example.com',
            'password' => 'Sup3rSecret!!',
            'password_confirmation' => 'Sup3rSecret!!',
        ])->assertRedirect(route('login'));

        $this->assertFileExists($this->lock);
        $this->assertTrue(User::where('email', 'owner@example.com')->firstOrFail()->hasRole('administrator'));
        $this->get('/setup-admin')->assertRedirect(route('home'));

        // Git deployments can lose local storage while retaining their database.
        unlink($this->lock);
        $this->get('/')->assertOk();
        $this->assertFileExists($this->lock);
        $this->get('/setup-admin')->assertRedirect(route('home'));
    }

    public function test_existing_non_admin_user_cannot_claim_administrator_setup(): void
    {
        User::factory()->create();
        $this->get('/setup-admin')->assertStatus(409);
        $this->post('/setup-admin', [
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => 'Sup3rSecret!!',
            'password_confirmation' => 'Sup3rSecret!!',
        ])->assertStatus(409);
        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.com']);
    }
}

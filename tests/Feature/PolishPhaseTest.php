<?php
namespace Tests\Feature;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class PolishPhaseTest extends TestCase
{
 use RefreshDatabase;

 public function test_spanish_and_french_translations_resolve(): void
 {
  $this->post('/locale/es');
  $this->get('/')->assertOk()->assertSee('Explorar');
  $this->post('/locale/fr');
  $this->get('/')->assertOk()->assertSee('Parcourir');
 }

 public function test_destructive_forms_declare_data_confirm(): void
 {
  $admin = User::factory()->create();
  $admin->roles()->attach(\App\Models\Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));
  $this->actingAs($admin)->get('/admin/earnings')->assertOk();
  $this->get('/admin/system')->assertOk()->assertSee('data-confirm', false);
  // No inline handlers remain anywhere in the compiled views we render.
  $this->assertSame([], glob(base_path('resources/views/**/onsubmit*')));
 }

 public function test_facebook_login_hidden_when_disabled_and_works_when_enabled(): void
 {
  $this->get('/login')->assertOk()->assertDontSee('Continue with Facebook');
  $this->get('/auth/facebook/redirect')->assertNotFound();

  Setting::put('auth.facebook.enabled', '1', 'auth');
  config(['services.facebook.client_id' => 'fb-app', 'services.facebook.secret' => 'fb-secret']);
  $this->get('/login')->assertOk()->assertSee('Continue with Facebook');
  $this->get('/auth/facebook/redirect')->assertRedirectContains('facebook.com');

  Http::fake([
   'graph.facebook.com/v19.0/oauth/access_token*' => Http::response(['access_token' => 'fb-token']),
   'graph.facebook.com/v19.0/me*' => Http::response(['id' => 'fb-77', 'name' => 'FB Buyer', 'email' => 'fb@example.com']),
  ]);
  Setting::put('features.registration', '1', 'features');
  $this->withSession(['facebook_oauth_state' => 'fbs'])->get('/auth/facebook/callback?code=c&state=fbs')->assertRedirect(route('dashboard'));
  $this->assertAuthenticated();
  $user = User::where('email', 'fb@example.com')->firstOrFail();
  $this->assertSame('fb-77', $user->facebook_id);
  $this->assertNotNull($user->email_verified_at);
 }

 public function test_facebook_without_shared_email_is_rejected(): void
 {
  Setting::put('auth.facebook.enabled', '1', 'auth');
  config(['services.facebook.client_id' => 'fb-app', 'services.facebook.secret' => 'fb-secret']);
  Http::fake([
   'graph.facebook.com/v19.0/oauth/access_token*' => Http::response(['access_token' => 'fb-token']),
   'graph.facebook.com/v19.0/me*' => Http::response(['id' => 'fb-88', 'name' => 'No Email']),
  ]);
  $this->withSession(['facebook_oauth_state' => 's'])->get('/auth/facebook/callback?code=c&state=s')->assertRedirect(route('login'));
  $this->assertGuest();
  $this->assertDatabaseCount('users', 0);
 }
}

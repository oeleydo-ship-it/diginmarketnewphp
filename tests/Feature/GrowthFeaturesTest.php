<?php
namespace Tests\Feature;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class GrowthFeaturesTest extends TestCase
{
 use RefreshDatabase;

 private function enableGoogle(): void
 {
  Setting::put('auth.google.enabled', '1', 'auth');
  config(['services.google.client_id' => 'client-123', 'services.google.secret' => 'secret-456']);
 }

 private function fakeGoogle(string $sub = 'g-100', string $email = 'buyer@gmail.com', string $name = 'Google Buyer'): void
 {
  Http::fake([
   'oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.token']),
   'openidconnect.googleapis.com/v1/userinfo' => Http::response(['sub' => $sub, 'email' => $email, 'email_verified' => true, 'name' => $name]),
  ]);
 }

 public function test_google_login_is_hidden_and_404s_when_disabled(): void
 {
  $this->get('/login')->assertOk()->assertDontSee('Continue with Google');
  $this->get('/auth/google/redirect')->assertNotFound();
 }

 public function test_google_redirect_sends_state_to_google(): void
 {
  $this->enableGoogle();
  $this->get('/login')->assertOk()->assertSee('Continue with Google');
  $response = $this->get('/auth/google/redirect');
  $response->assertRedirectContains('accounts.google.com');
  $this->assertStringContainsString('client-123', $response->headers->get('Location'));
  $this->assertNotEmpty(session('google_oauth_state'));
 }

 public function test_google_callback_creates_customer_and_logs_in(): void
 {
  $this->enableGoogle();
  $this->fakeGoogle();
  Setting::put('features.registration', '1', 'features');
  session()->put('google_oauth_state', 'state-abc');
  $this->withSession(['google_oauth_state' => 'state-abc'])->get('/auth/google/callback?code=auth-code&state=state-abc')->assertRedirect(route('dashboard'));
  $this->assertAuthenticated();
  $user = User::where('email', 'buyer@gmail.com')->firstOrFail();
  $this->assertSame('g-100', $user->google_id);
  $this->assertNotNull($user->email_verified_at);
  $this->assertTrue($user->roles()->where('slug', 'customer')->exists());
 }

 public function test_google_callback_links_existing_account_by_email(): void
 {
  $this->enableGoogle();
  $this->fakeGoogle('g-200', 'existing@example.com');
  $existing = User::factory()->create(['email' => 'existing@example.com']);
  $this->withSession(['google_oauth_state' => 's1'])->get('/auth/google/callback?code=c&state=s1')->assertRedirect(route('dashboard'));
  $this->assertSame('g-200', $existing->fresh()->google_id);
  $this->assertAuthenticatedAs($existing);
  $this->assertSame(1, User::where('email', 'existing@example.com')->count());
 }

 public function test_google_callback_rejects_bad_state(): void
 {
  $this->enableGoogle();
  $this->fakeGoogle();
  $this->withSession(['google_oauth_state' => 'real'])->get('/auth/google/callback?code=c&state=forged')->assertRedirect(route('login'));
  $this->assertGuest();
 }

 public function test_google_login_still_challenges_two_factor_accounts(): void
 {
  $this->enableGoogle();
  $this->fakeGoogle('g-300', 'tfa@example.com');
  $user = User::factory()->create(['email' => 'tfa@example.com']);
  $user->forceFill(['two_factor_secret' => app(TwoFactorService::class)->generateSecret(), 'two_factor_confirmed_at' => now()])->save();
  $this->withSession(['google_oauth_state' => 's2'])->get('/auth/google/callback?code=c&state=s2')->assertRedirect(route('two-factor.challenge'));
  $this->assertGuest();
  $this->assertSame($user->id, session('2fa.user_id'));
 }

 public function test_cookie_banner_shows_until_consent_cookie_is_set(): void
 {
  $this->get('/')->assertOk()->assertSee('Essential only')->assertSee('Accept all');
  $this->withUnencryptedCookie('dm_cookie_consent', 'all')->get('/')->assertOk()->assertDontSee('Essential only');
  Setting::put('features.cookie_consent', '0', 'features');
  $this->get('/')->assertOk()->assertDontSee('Essential only');
 }

 public function test_tawk_embed_and_csp_appear_only_when_configured(): void
 {
  $response = $this->get('/');
  $response->assertOk()->assertDontSee('tawk-embed');
  $this->assertStringNotContainsString('tawk.to', (string) $response->headers->get('Content-Security-Policy'));
  config(['services.tawk.property_id' => 'abc123prop', 'services.tawk.widget_id' => 'default']);
  $response = $this->get('/');
  $response->assertOk()->assertSee('tawk-embed', false)->assertSee('abc123prop/default', false);
  $this->assertStringContainsString('https://*.tawk.to', (string) $response->headers->get('Content-Security-Policy'));
 }
}

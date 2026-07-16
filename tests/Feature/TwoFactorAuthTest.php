<?php
namespace Tests\Feature;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class TwoFactorAuthTest extends TestCase
{
 use RefreshDatabase;
 private function enrolled(TwoFactorService $svc): array
 {
  $secret = $svc->generateSecret();
  $user = User::factory()->create([
   'two_factor_secret' => $secret,
   'two_factor_recovery_codes' => ['AAAAA-BBBBB', 'CCCCC-DDDDD'],
   'two_factor_confirmed_at' => now(),
  ]);
  return [$user, $secret];
 }

 public function test_security_page_and_enrolment_view_render(): void
 {
  $user = User::factory()->create();
  $this->actingAs($user)->get('/account/security')->assertOk()->assertSee('Two-factor authentication');
  // After staging a secret, the page shows the setup key and confirm prompt.
  $this->actingAs($user)->post('/account/two-factor');
  $this->actingAs($user)->get('/account/security')->assertOk()->assertSee('Enter the 6-digit code', false)->assertSee(trim(chunk_split((string) $user->fresh()->two_factor_secret, 4, ' ')), false);
 }

 public function test_challenge_page_renders_when_pending(): void
 {
  $svc = app(TwoFactorService::class);
  [$user] = $this->enrolled($svc);
  $user->forceFill(['password' => bcrypt('secret-pass'), 'status' => 'active'])->save();
  $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass']);
  $this->get('/two-factor-challenge')->assertOk()->assertSee('Two-factor verification');
 }

 public function test_challenge_redirects_to_login_without_a_pending_user(): void
 {
  $this->get('/two-factor-challenge')->assertRedirect(route('login'));
 }

 public function test_enable_stages_a_secret_but_does_not_enforce_until_confirmed(): void
 {
  $user = User::factory()->create();
  $this->actingAs($user)->post('/account/two-factor')->assertRedirect();
  $user->refresh();
  $this->assertNotNull($user->two_factor_secret);
  $this->assertNull($user->two_factor_confirmed_at);
  $this->assertFalse($user->hasTwoFactorEnabled());
 }

 public function test_confirm_requires_a_valid_code_and_then_activates(): void
 {
  $svc = app(TwoFactorService::class);
  $user = User::factory()->create();
  $this->actingAs($user)->post('/account/two-factor');
  $user->refresh();
  $this->actingAs($user)->post('/account/two-factor/confirm', ['code' => '000000'])->assertSessionHasErrors('code');
  $valid = $this->currentCode($svc, (string) $user->two_factor_secret);
  $this->actingAs($user)->post('/account/two-factor/confirm', ['code' => $valid])->assertRedirect(route('account.security'));
  $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
 }

 public function test_login_with_2fa_defers_session_until_code_verified(): void
 {
  $svc = app(TwoFactorService::class);
  [$user] = $this->enrolled($svc);
  $user->forceFill(['password' => bcrypt('secret-pass'), 'status' => 'active'])->save();
  $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass'])->assertRedirect(route('two-factor.challenge'));
  $this->assertGuest();
  $bad = $this->post('/two-factor-challenge', ['code' => '000000']);
  $bad->assertSessionHasErrors('code');
  $this->assertGuest();
  $code = $this->currentCode($svc, (string) $user->two_factor_secret);
  $this->post('/two-factor-challenge', ['code' => $code])->assertRedirect(route('dashboard'));
  $this->assertAuthenticatedAs($user->fresh());
 }

 public function test_recovery_code_completes_login_and_is_consumed(): void
 {
  $svc = app(TwoFactorService::class);
  [$user] = $this->enrolled($svc);
  $user->forceFill(['password' => bcrypt('secret-pass'), 'status' => 'active'])->save();
  $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass']);
  $this->post('/two-factor-challenge', ['recovery_code' => 'AAAAA-BBBBB'])->assertRedirect(route('dashboard'));
  $this->assertAuthenticatedAs($user->fresh());
  $this->assertNotContains('AAAAA-BBBBB', (array) $user->fresh()->two_factor_recovery_codes);
 }

 public function test_login_without_2fa_is_unchanged(): void
 {
  $user = User::factory()->create(['password' => bcrypt('secret-pass'), 'status' => 'active']);
  $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass'])->assertRedirect(route('dashboard'));
  $this->assertAuthenticatedAs($user);
 }

 public function test_disable_clears_all_2fa_state(): void
 {
  $svc = app(TwoFactorService::class);
  [$user] = $this->enrolled($svc);
  $this->actingAs($user)->delete('/account/two-factor')->assertRedirect();
  $user->refresh();
  $this->assertNull($user->two_factor_secret);
  $this->assertNull($user->two_factor_confirmed_at);
  $this->assertFalse($user->hasTwoFactorEnabled());
 }

 private function currentCode(TwoFactorService $svc, string $secret): string
 {
  return $svc->currentCode($secret);
 }
}

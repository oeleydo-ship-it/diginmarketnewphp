<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
class PasswordResetAndVerificationTest extends TestCase
{
 use RefreshDatabase;

 public function test_login_page_links_to_password_reset(): void
 {
  $this->get('/login')->assertOk()->assertSee('Forgot password?');
  $this->get('/forgot-password')->assertOk()->assertSee('Email reset link');
 }

 public function test_full_password_reset_flow(): void
 {
  Notification::fake();
  $user = User::factory()->create(['email' => 'reset@example.com']);
  $this->post('/forgot-password', ['email' => 'reset@example.com'])->assertRedirect();
  $token = null;
  Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
   $token = $notification->token;
   return true;
  });
  $this->get('/reset-password/'.$token.'?email=reset@example.com')->assertOk()->assertSee('Choose a new password');
  $this->post('/reset-password', ['token' => $token, 'email' => 'reset@example.com', 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'])->assertRedirect(route('login'));
  $this->post('/login', ['email' => 'reset@example.com', 'password' => 'brand-new-password'])->assertRedirect(route('dashboard'));
  $this->assertAuthenticated();
 }

 public function test_reset_does_not_reveal_whether_an_email_exists(): void
 {
  Notification::fake();
  $this->post('/forgot-password', ['email' => 'ghost@example.com'])->assertRedirect()->assertSessionHas('status');
  Notification::assertNothingSent();
 }

 public function test_registration_sends_verification_email(): void
 {
  Notification::fake();
  \App\Models\Role::firstOrCreate(['slug' => 'customer'], ['name' => 'Customer']);
  \App\Models\Setting::put('features.email_verification', '1', 'features');
  $this->post('/register', ['name' => 'New Buyer', 'email' => 'new@example.com', 'password' => 'Password-123456', 'password_confirmation' => 'Password-123456'])->assertRedirect();
  Notification::assertSentTo(User::where('email', 'new@example.com')->firstOrFail(), VerifyEmail::class);
 }

 public function test_registration_skips_verification_email_when_feature_is_disabled(): void
 {
  Notification::fake();
  \App\Models\Role::firstOrCreate(['slug' => 'customer'], ['name' => 'Customer']);
  \App\Models\Setting::put('features.email_verification', '0', 'features');

  $this->post('/register', ['name' => 'Fast Buyer', 'email' => 'fast@example.com', 'password' => 'Password-123456', 'password_confirmation' => 'Password-123456'])->assertRedirect(route('dashboard'));

  $user = User::where('email', 'fast@example.com')->firstOrFail();
  Notification::assertNotSentTo($user, VerifyEmail::class);
  $this->assertNotNull($user->email_verified_at);
 }

 public function test_verification_notice_is_reachable_and_resend_works(): void
 {
  Notification::fake();
  \App\Models\Setting::put('features.email_verification', '1', 'features');
  $user = User::factory()->unverified()->create();

  $this->actingAs($user)->get('/verify-email')->assertOk()->assertSee('Verify your email address');
  $this->actingAs($user)->post('/verify-email/resend')->assertRedirect();

  Notification::assertSentTo($user, VerifyEmail::class);
 }

 public function test_unverified_user_cannot_checkout_until_verified(): void
 {
  \App\Models\Setting::put('features.email_verification', '1', 'features');
  $user = User::factory()->unverified()->create();
  $this->actingAs($user)->post('/checkout')->assertRedirect(route('verification.notice'));
  $this->get('/verify-email')->assertOk()->assertSee('Verify your email address');
  // Signed verification link flips the account and unblocks checkout.
  $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)]);
  $this->get($url)->assertRedirect(route('dashboard'));
  $this->assertNotNull($user->fresh()->email_verified_at);
  // Now the guard passes (422 = empty cart, i.e. past the middleware).
  $this->post('/checkout')->assertStatus(422);
 }

 public function test_unverified_user_cannot_apply_to_sell(): void
 {
  \App\Models\Setting::put('features.email_verification', '1', 'features');
  $user = User::factory()->unverified()->create();
  $this->actingAs($user)->post('/sell/apply', [])->assertRedirect(route('verification.notice'));
 }

 public function test_unverified_user_can_checkout_when_verification_is_disabled(): void
 {
  \App\Models\Setting::put('features.email_verification', '0', 'features');
  $user = User::factory()->unverified()->create();

  $this->actingAs($user)->post('/checkout')->assertStatus(422);
  $this->actingAs($user)->get('/verify-email')->assertRedirect(route('dashboard'));
 }

 public function test_unverified_user_can_apply_to_sell_when_verification_is_disabled(): void
 {
  \App\Models\Setting::put('features.email_verification', '0', 'features');
  $user = User::factory()->unverified()->create();

  $this->actingAs($user)->post('/sell/apply', [
   'full_name' => 'Studio One Owner',
   'display_name' => 'Studio One',
   'username' => 'studio-one',
   'country' => 'AE',
   'address' => '123 Market Street',
   'city' => 'Dubai',
   'biography' => 'Long enough biography text for a seller application.',
  ])->assertRedirect(route('dashboard'));

  $this->assertDatabaseHas('seller_profiles', ['user_id' => $user->id, 'username' => 'studio-one']);
 }
}

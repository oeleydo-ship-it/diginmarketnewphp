<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class LocalizationTest extends TestCase
{
 use RefreshDatabase;
 public function test_switching_locale_persists_and_renders_rtl(): void
 {
  $this->post('/locale/ar')->assertRedirect();
  $response=$this->get('/');
  $response->assertOk();
  $response->assertSee('dir="rtl"',false);
  $response->assertSee('تصفح',false); // "Browse" in Arabic proves the catalog loaded.
 }
 public function test_signed_in_user_locale_is_saved(): void
 {
  $user=User::factory()->create();
  $this->actingAs($user)->post('/locale/ar')->assertRedirect();
  $this->assertSame('ar',$user->fresh()->locale);
 }
 public function test_unsupported_locale_is_rejected(): void
 {
  $this->post('/locale/zz')->assertNotFound();
 }
 public function test_default_locale_renders_ltr_english(): void
 {
  $response=$this->get('/');
  $response->assertOk();
  $response->assertSee('dir="ltr"',false);
  $response->assertSee('>Browse<',false);
 }
}

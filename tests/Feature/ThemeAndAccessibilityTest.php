<?php
namespace Tests\Feature;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ThemeAndAccessibilityTest extends TestCase
{
 use RefreshDatabase;
 public function test_homepage_ships_theme_bootstrap_toggle_and_skip_link(): void
 {
  $response=$this->get('/');
  $response->assertOk()
   ->assertSee(SecurityHeaders::THEME_BOOTSTRAP,false)
   ->assertSee('data-theme-toggle',false)
   ->assertSee('aria-label="Toggle dark mode"',false)
   ->assertSee('Skip to main content')
   ->assertSee('id="main-content"',false);
 }
 public function test_csp_allows_exactly_the_theme_bootstrap_inline_script(): void
 {
  $csp=$this->get('/')->headers->get('Content-Security-Policy');
  $hash="'sha256-".base64_encode(hash('sha256',SecurityHeaders::THEME_BOOTSTRAP,true))."'";
  $this->assertStringContainsString("script-src 'self' ".$hash,$csp);
  $this->assertStringNotContainsString("unsafe-inline'; script",$csp);
 }
}

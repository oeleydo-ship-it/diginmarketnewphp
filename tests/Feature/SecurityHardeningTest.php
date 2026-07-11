<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SecurityHardeningTest extends TestCase
{
 use RefreshDatabase;
 public function test_security_headers_are_present_on_web_responses(): void
 {
  $response=$this->get('/');
  $response->assertOk()->assertHeader('X-Content-Type-Options','nosniff')->assertHeader('X-Frame-Options','DENY')->assertHeader('Referrer-Policy','strict-origin-when-cross-origin');
  $csp=$response->headers->get('Content-Security-Policy');
  $this->assertStringContainsString("default-src 'self'",$csp);
  $this->assertStringContainsString("frame-ancestors 'none'",$csp);
  $this->assertStringContainsString('https://checkout.stripe.com',$csp);
 }
 public function test_health_endpoint_responds(): void
 {
  $this->get('/up')->assertOk();
 }
 public function test_sitemap_renders_valid_xml(): void
 {
  $response=$this->get('/sitemap.xml');
  $response->assertOk()->assertHeader('Content-Type','application/xml');
  $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>',$response->getContent());
 }
}

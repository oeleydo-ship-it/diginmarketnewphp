<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_head_includes_google_analytics_when_configured(): void
    {
        Setting::put('seo.google_analytics_measurement_id', 'G-TEST-123456', 'seo');
        Setting::put('seo.google_site_verification', null, 'seo');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('gtag/js?id=G-TEST-123456');
        $response->assertSee('google-analytics-measurement-id');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString('https://www.googletagmanager.com', $csp);
        $this->assertStringContainsString('https://www.google-analytics.com', $csp);
    }

    public function test_head_includes_google_site_verification_when_configured(): void
    {
        Setting::put('seo.google_analytics_measurement_id', null, 'seo');
        Setting::put('seo.google_site_verification', 'abc123', 'seo');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('google-site-verification');
        $response->assertSee('content="abc123"', false);
    }
}


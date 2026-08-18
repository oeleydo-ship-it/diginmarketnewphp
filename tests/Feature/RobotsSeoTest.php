<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RobotsSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_includes_sitemap_url(): void
    {
        Setting::put('seo.sitemap_in_robots', '1', 'seo');
        Setting::put('seo.robots_extra', "Disallow: /admin\n", 'seo');

        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
            ->assertSee('Sitemap: '.url('/sitemap.xml'))
            ->assertSee('Disallow: /admin');
    }
}


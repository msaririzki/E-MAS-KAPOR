<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_exposes_core_seo_signals(): void
    {
        config()->set('app.url', 'https://birologpoldantb.id');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Sistem Informasi Manajemen Kapor Biro Logistik Polda NTB');
        $response->assertSee('https://birologpoldantb.id/', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('biro logistik polda ntb', false);
    }

    public function test_sitemap_lists_only_the_public_homepage(): void
    {
        config()->set('app.url', 'https://birologpoldantb.id');

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<urlset', false);
        $response->assertSee('https://birologpoldantb.id/', false);
        $response->assertDontSee('/login', false);
    }

    public function test_robots_file_points_to_sitemap_and_blocks_private_sections(): void
    {
        config()->set('app.url', 'https://birologpoldantb.id');

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('Disallow: /admin', false);
        $response->assertSee('Disallow: /admin-satker', false);
        $response->assertSee('Sitemap: https://birologpoldantb.id/sitemap.xml', false);
    }

    public function test_login_page_is_marked_noindex(): void
    {
        config()->set('app.url', 'https://birologpoldantb.id');

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $response->assertSee('meta name="robots" content="noindex, nofollow"', false);
        $response->assertSee('https://birologpoldantb.id/login', false);
    }
}

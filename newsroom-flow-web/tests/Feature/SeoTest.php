<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_html_has_server_rendered_share_tags(): void
    {
        // The app is Inertia (no SSR), so JS-less social scrapers read these
        // server-rendered defaults from app.blade.php for link previews.
        $this->get('/')->assertOk()
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('og-default.png', false)
            ->assertSee('name="twitter:card"', false)
            ->assertSee('name="description"', false);
    }

    public function test_sitemap_lists_public_pages_including_world_news(): void
    {
        $this->get('/sitemap.xml')->assertOk()
            ->assertSee('/world-news', false)
            ->assertSee('/pricing', false)
            ->assertSee('/faq', false);
    }

    public function test_robots_allows_marketing_pages_and_blocks_the_app(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('/world-news', $robots);
        $this->assertStringContainsString('Disallow: /dashboard', $robots);
        $this->assertStringContainsString('Sitemap:', $robots);
    }

    public function test_og_default_image_exists(): void
    {
        $this->assertFileExists(public_path('img/og-default.png'));
    }
}

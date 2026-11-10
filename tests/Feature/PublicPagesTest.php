<?php

namespace Tests\Feature;

use App\Content\ContentSync;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\WritesContentFixtures;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;
    use WritesContentFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpContentDir();
        config(['responsecache.enabled' => false]);

        $this->writeEntry('verified-one', ['date' => '2025-03-14']);
        $this->writeEntry('verified-two', ['date' => '2022-01-19']);
        $this->writeEntry('draft', ['status' => 'needs-review']);
        app(ContentSync::class)->sync($this->contentDir);
    }

    protected function tearDown(): void
    {
        $this->tearDownContentDir();
        parent::tearDown();
    }

    public function test_home_lists_verified_entries_newest_first(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home', false)
                ->has('entries', 2)
                ->where('entries.0.slug', 'verified-one')
                ->where('entries.1.slug', 'verified-two')
                ->has('categories'));
    }

    public function test_unverified_entries_visible_when_enabled(): void
    {
        config(['csfo.show_unverified' => true]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->has('entries', 3));
        $this->get('/entries/draft')->assertOk();
    }

    public function test_entry_page_renders_detail_and_meta(): void
    {
        $this->get('/entries/verified-one')
            ->assertOk()
            ->assertSee('<title>Sample entry — ', false)
            ->assertSee('property="og:description" content="Something happened."', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Entry', false)
                ->where('entry.slug', 'verified-one')
                ->has('entry.sources', 1));
    }

    public function test_unverified_and_missing_entries_404(): void
    {
        $this->get('/entries/draft')->assertNotFound();
        $this->get('/entries/does-not-exist')->assertNotFound();
    }

    public function test_full_page_responses_are_cdn_cacheable(): void
    {
        $response = $this->get('/');

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('s-maxage='.config('csfo.cache.s_maxage'), $cacheControl);
        $this->assertStringContainsString('X-Inertia', $response->headers->get('Vary'));
    }

    public function test_inertia_xhr_responses_are_never_shared(): void
    {
        $version = app(HandleInertiaRequests::class)->version(request());

        $this->get('/', ['X-Inertia' => 'true', 'X-Inertia-Version' => $version])
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_public_pages_set_no_cookies(): void
    {
        $response = $this->get('/');

        $this->assertEmpty($response->headers->getCookies());
    }

    public function test_rate_limit_applies_per_ip(): void
    {
        config(['csfo.rate_limit' => 2]);
        RateLimiter::clear('public');

        $this->get('/')->assertOk();
        $this->get('/')->assertOk();
        $this->get('/')->assertStatus(429);
        $this->get('/', ['REMOTE_ADDR' => '10.0.0.2'])->assertOk();
    }
}

<?php

namespace Tests\Feature;

use App\Content\ContentSync;
use App\Content\InvalidContentException;
use App\Content\PolicyLoader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\WritesContentFixtures;
use Tests\TestCase;

class PoliciesTest extends TestCase
{
    use RefreshDatabase;
    use WritesContentFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpContentDir();
        config(['responsecache.enabled' => false]);

        $this->writeEntry('min-wage-vote');
        app(ContentSync::class)->sync($this->contentDir);
    }

    protected function tearDown(): void
    {
        $this->tearDownContentDir();
        parent::tearDown();
    }

    public function test_loader_rejects_unknown_related_entries(): void
    {
        $this->writePolicy('wages', ['related_entries' => ['min-wage-vote', 'no-such-entry']]);

        try {
            app(PolicyLoader::class)->load($this->contentDir.'/policies', ['min-wage-vote']);
            $this->fail('Expected InvalidContentException');
        } catch (InvalidContentException $e) {
            $this->assertStringContainsString('no-such-entry', $e->errors['wages.yaml'][0]);
        }
    }

    public function test_loader_requires_review_note_for_needs_review(): void
    {
        $this->writePolicy('wages', ['status' => 'needs-review', 'review_note' => null]);

        $this->expectException(InvalidContentException::class);

        app(PolicyLoader::class)->load($this->contentDir.'/policies');
    }

    public function test_index_orders_by_category_then_title(): void
    {
        $this->writePolicy('courts', ['category' => 'courts', 'title' => 'A court']);
        $this->writePolicy('health-b', ['category' => 'healthcare', 'title' => 'B health']);
        $this->writePolicy('health-a', ['category' => 'healthcare', 'title' => 'A health']);

        $this->get('/policies')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Policies', false)
            ->where('policies.0.slug', 'health-a')
            ->where('policies.1.slug', 'health-b')
            ->where('policies.2.slug', 'courts')
            ->where('policies.0.stat.value', '42%'));
    }

    public function test_policy_page_lists_related_entries(): void
    {
        $this->writePolicy('wages', ['related_entries' => ['min-wage-vote']]);

        $this->get('/policies/wages')
            ->assertOk()
            ->assertSee('<title>Sample policy — ', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Policy', false)
                ->where('policy.slug', 'wages')
                ->where('related.0.slug', 'min-wage-vote'));
    }

    public function test_entry_page_links_back_to_related_policies(): void
    {
        $this->writePolicy('wages', ['related_entries' => ['min-wage-vote']]);

        $this->get('/entries/min-wage-vote')->assertInertia(fn (Assert $page) => $page
            ->where('policies.0.slug', 'wages'));
    }

    public function test_unverified_policies_hidden_when_disabled(): void
    {
        $this->writePolicy('pending', ['status' => 'needs-review']);

        $this->get('/policies/pending')->assertNotFound();

        config(['csfo.show_unverified' => true]);
        $this->get('/policies/pending')->assertOk();
    }

    public function test_unknown_policy_404s(): void
    {
        $this->get('/policies/nope')->assertNotFound();
    }

    public function test_sync_check_validates_policies(): void
    {
        $this->writePolicy('wages', ['related_entries' => ['missing']]);

        $this->artisan('content:sync --check')->expectsOutputToContain('wages.yaml')->assertFailed();
    }
}

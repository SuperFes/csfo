<?php

namespace Tests\Feature;

use App\Content\ContentSync;
use App\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WritesContentFixtures;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;
    use WritesContentFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpContentDir();
        config(['responsecache.enabled' => false]);
    }

    protected function tearDown(): void
    {
        $this->tearDownContentDir();
        parent::tearDown();
    }

    public function test_feed_lists_newest_additions_first(): void
    {
        $this->writeEntry('old-event', ['title' => 'Old event & aftermath', 'date' => '2007-01-01']);
        $this->writeEntry('recent-event', ['title' => 'Recent event', 'date' => '2026-11-01']);
        app(ContentSync::class)->sync($this->contentDir);
        Entry::where('slug', 'recent-event')->update(['created_at' => now()->subDay()]);

        $response = $this->get('/feed.xml')->assertOk();

        $this->assertStringStartsWith('application/rss+xml', $response->headers->get('Content-Type'));
        $xml = simplexml_load_string($response->getContent());
        $this->assertSame('Old event & aftermath', (string) $xml->channel->item[0]->title);
        $this->assertSame(url('/entries/old-event'), (string) $xml->channel->item[0]->link);
        $this->assertSame('Recent event', (string) $xml->channel->item[1]->title);
    }

    public function test_feed_hides_unverified_when_disabled(): void
    {
        config(['csfo.show_unverified' => false]);
        $this->writeEntry('pending', ['status' => 'needs-review']);
        app(ContentSync::class)->sync($this->contentDir);

        $xml = simplexml_load_string($this->get('/feed.xml')->getContent());

        $this->assertCount(0, $xml->channel->item);
    }

    public function test_sitemap_lists_every_public_page(): void
    {
        $this->writeEntry('an-entry');
        app(ContentSync::class)->sync($this->contentDir);
        $this->writePolicy('wages');
        $this->writeMember('chuck-schumer');
        $this->writeRace('senate-leader-2026');

        $response = $this->get('/sitemap.xml')->assertOk();

        $xml = simplexml_load_string($response->getContent());
        $locs = [];
        foreach ($xml->url as $url) {
            $locs[] = (string) $url->loc;
        }

        foreach (['/', '/policies', '/money', '/entries/an-entry', '/policies/wages', '/money/chuck-schumer', '/races/senate-leader-2026'] as $path) {
            $this->assertContains(url($path), $locs);
        }
    }
}

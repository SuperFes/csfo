<?php

namespace Tests\Feature;

use App\Content\ContentSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\WritesContentFixtures;
use Tests\TestCase;

class RacesTest extends TestCase
{
    use RefreshDatabase;
    use WritesContentFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpContentDir();
        config(['responsecache.enabled' => false]);

        $this->writeEntry('challenge-announced', ['title' => 'Challenge announced']);
        app(ContentSync::class)->sync($this->contentDir);
    }

    protected function tearDown(): void
    {
        $this->tearDownContentDir();
        parent::tearDown();
    }

    public function test_race_page_shows_groups_and_related_entries(): void
    {
        $this->writeRace('senate-leader-2026', [
            'title' => 'Senate Democratic leader',
            'related_entries' => ['challenge-announced'],
            'commitments' => [$this->commitmentData(['name' => 'Bernie Sanders'])],
        ]);

        $this->get('/races/senate-leader-2026')
            ->assertOk()
            ->assertSee('<title>Senate Democratic leader — ', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Race', false)
                ->where('race.groups.0.commitments.0.name', 'Bernie Sanders')
                ->where('race.unstated', 54)
                ->where('related.0.slug', 'challenge-announced'));
    }

    public function test_unknown_race_is_404(): void
    {
        $this->get('/races/nope')->assertNotFound();
    }

    public function test_unverified_race_hidden_when_unverified_content_is_off(): void
    {
        config(['csfo.show_unverified' => false]);
        $this->writeRace('senate-leader-2026', ['status' => 'needs-review']);

        $this->get('/races/senate-leader-2026')->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Content\ContentSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\WritesContentFixtures;
use Tests\TestCase;

class MembersTest extends TestCase
{
    use RefreshDatabase;
    use WritesContentFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpContentDir();
        config(['responsecache.enabled' => false]);

        $this->writeEntry('min-wage-vote', ['people' => ['chuck-schumer']]);
        $this->writeEntry('swipe-fees', ['date' => '2024-01-01']);
        app(ContentSync::class)->sync($this->contentDir);
    }

    protected function tearDown(): void
    {
        $this->tearDownContentDir();
        parent::tearDown();
    }

    public function test_index_lists_leadership_first_then_senate_then_name(): void
    {
        $this->writeMember('house-blocker', ['name' => 'A House', 'chamber' => 'house', 'district' => '3', 'role' => null, 'inclusion' => ['blocker'], 'votes' => [$this->voteData(['policy' => 'wages'])]]);
        $this->writeMember('senate-blocker', ['name' => 'B Senate', 'role' => null, 'inclusion' => ['blocker'], 'votes' => [$this->voteData(['policy' => 'wages'])]]);
        $this->writeMember('leader', ['name' => 'Z Leader']);

        $this->get('/money')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Money', false)
            ->where('members.0.slug', 'leader')
            ->where('members.1.slug', 'senate-blocker')
            ->where('members.2.slug', 'house-blocker')
            ->where('members.0.stat.value', '$1M'));
    }

    public function test_member_page_shows_votes_entries_and_policy_titles(): void
    {
        $this->writePolicy('wages', ['title' => 'A living wage']);
        $this->writePolicy('unrelated', ['title' => 'Not voted on']);
        $this->writeMember('chuck-schumer', [
            'name' => 'Chuck Schumer',
            'related_entries' => ['swipe-fees'],
            'votes' => [$this->voteData(['policy' => 'wages'])],
        ]);

        $this->get('/money/chuck-schumer')
            ->assertOk()
            ->assertSee('<title>Chuck Schumer — Follow the money — ', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member', false)
                ->where('member.votes.0.policy', 'wages')
                ->has('record', 1)
                ->where('record.0.slug', 'min-wage-vote')
                ->has('related', 1)
                ->where('related.0.slug', 'swipe-fees')
                ->where('policyTitles', ['wages' => 'A living wage']));
    }

    public function test_unknown_member_is_404(): void
    {
        $this->get('/money/nobody')->assertNotFound();
    }

    public function test_unverified_members_hidden_when_disabled(): void
    {
        config(['csfo.show_unverified' => false]);
        $this->writeMember('leader', ['status' => 'needs-review']);

        $this->get('/money/leader')->assertNotFound();
    }

    public function test_policy_and_entry_pages_link_members(): void
    {
        $this->writePolicy('wages');
        $this->writeMember('voter', ['votes' => [$this->voteData(['policy' => 'wages'])]]);
        $this->writeMember('bystander', ['related_entries' => ['min-wage-vote']]);

        $this->get('/policies/wages')->assertInertia(fn (Assert $page) => $page
            ->has('members', 1)
            ->where('members.0.slug', 'voter'));

        $this->get('/entries/min-wage-vote')->assertInertia(fn (Assert $page) => $page
            ->has('members', 1)
            ->where('members.0.slug', 'bystander')
            ->where('members.0.subject', false));
    }

    public function test_entry_page_lists_subjects_first_in_entry_order(): void
    {
        $this->writeEntry('cloture', ['people' => ['dick-durbin', 'chuck-schumer']]);
        app(ContentSync::class)->sync($this->contentDir);
        $this->writeMember('chuck-schumer', ['name' => 'Chuck Schumer']);
        $this->writeMember('dick-durbin', ['name' => 'Dick Durbin']);
        $this->writeMember('angus-king', ['name' => 'Angus King', 'related_entries' => ['cloture']]);

        $this->get('/entries/cloture')->assertInertia(fn (Assert $page) => $page
            ->where('members.0.slug', 'dick-durbin')
            ->where('members.0.short_name', 'Durbin')
            ->where('members.0.subject', true)
            ->where('members.1.slug', 'chuck-schumer')
            ->where('members.2.slug', 'angus-king')
            ->where('members.2.subject', false));
    }

    public function test_home_names_only_people_with_entries(): void
    {
        $this->writeMember('chuck-schumer', ['name' => 'Chuck Schumer']);
        $this->writeMember('dick-durbin', ['name' => 'Dick Durbin']);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('people', ['chuck-schumer' => ['name' => 'Chuck Schumer', 'short_name' => 'Schumer']])
            ->where('entries.0.people', ['chuck-schumer']));
    }

    public function test_unverified_people_are_not_named(): void
    {
        config(['csfo.show_unverified' => false]);
        $this->writeMember('chuck-schumer', ['status' => 'needs-review']);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('people', []));
    }
}

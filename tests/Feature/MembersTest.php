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

        $this->writeEntry('min-wage-vote');
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
            'related_entries' => ['min-wage-vote'],
            'votes' => [$this->voteData(['policy' => 'wages'])],
        ]);

        $this->get('/money/chuck-schumer')
            ->assertOk()
            ->assertSee('<title>Chuck Schumer — Follow the money — ', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member', false)
                ->where('member.votes.0.policy', 'wages')
                ->where('related.0.slug', 'min-wage-vote')
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
            ->where('members.0.slug', 'bystander'));
    }
}

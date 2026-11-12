<?php

namespace Tests\Feature;

use App\Content\InvalidContentException;
use App\Content\MemberLoader;
use Tests\Concerns\WritesContentFixtures;
use Tests\TestCase;

class MemberLoaderTest extends TestCase
{
    use WritesContentFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpContentDir();
    }

    protected function tearDown(): void
    {
        $this->tearDownContentDir();
        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function errorsFor(string $file, ?array $entries = null, ?array $policies = null): array
    {
        try {
            app(MemberLoader::class)->load($this->contentDir.'/members', $entries, $policies);
            $this->fail('Expected InvalidContentException');
        } catch (InvalidContentException $e) {
            return $e->errors[$file];
        }
    }

    public function test_loads_and_normalizes(): void
    {
        $this->writeMember('chuck-schumer', [
            'votes' => [
                $this->voteData(['date' => '2024-01-01', 'title' => 'Older']),
                $this->voteData(['date' => '2025-06-01', 'title' => 'Newer', 'position' => 'not-voting']),
            ],
        ]);

        $member = app(MemberLoader::class)->load($this->contentDir.'/members')['chuck-schumer'];

        $this->assertSame('Senate', $member['chamber_label']);
        $this->assertSame(['Party leadership'], $member['inclusion_labels']);
        $this->assertSame(['Newer', 'Older'], array_column($member['votes'], 'title'));
        $this->assertSame('Did not vote', $member['votes'][0]['position_label']);
        $this->assertSame('Blackstone', $member['employers'][0]['name']);
    }

    public function test_blocker_needs_a_vote_tied_to_a_policy(): void
    {
        $this->writeMember('blocker', ['inclusion' => ['blocker'], 'votes' => [$this->voteData()]]);

        $this->assertStringContainsString('Blockers need', $this->errorsFor('blocker.yaml')[0]);
    }

    public function test_blocker_with_policy_vote_is_valid(): void
    {
        $this->writeMember('blocker', ['inclusion' => ['blocker'], 'votes' => [$this->voteData(['policy' => 'wages'])]]);

        $members = app(MemberLoader::class)->load($this->contentDir.'/members', [], ['wages']);

        $this->assertArrayHasKey('blocker', $members);
    }

    public function test_leadership_needs_a_role(): void
    {
        $this->writeMember('leader', ['role' => null]);

        $this->assertStringContainsString('need a role', $this->errorsFor('leader.yaml')[0]);
    }

    public function test_rejects_unknown_entries_and_policies(): void
    {
        $this->writeMember('leader', [
            'related_entries' => ['no-such-entry'],
            'votes' => [$this->voteData(['policy' => 'no-such-policy'])],
        ]);

        $errors = implode("\n", $this->errorsFor('leader.yaml', [], []));

        $this->assertStringContainsString('no-such-entry', $errors);
        $this->assertStringContainsString('no-such-policy', $errors);
    }

    public function test_district_only_allowed_for_house(): void
    {
        $this->writeMember('leader', ['district' => '8']);

        $this->assertNotEmpty($this->errorsFor('leader.yaml'));
    }

    public function test_requires_review_note_for_needs_review(): void
    {
        $this->writeMember('leader', ['status' => 'needs-review', 'review_note' => null]);

        $this->assertNotEmpty($this->errorsFor('leader.yaml'));
    }
}

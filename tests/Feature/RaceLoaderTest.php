<?php

namespace Tests\Feature;

use App\Content\InvalidContentException;
use App\Content\RaceLoader;
use Tests\Concerns\WritesContentFixtures;
use Tests\TestCase;

class RaceLoaderTest extends TestCase
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
     * @return list<string>
     */
    private function errorsFor(string $file, ?array $entries = null): array
    {
        try {
            app(RaceLoader::class)->load($this->contentDir.'/races', $entries);
            $this->fail('Expected InvalidContentException');
        } catch (InvalidContentException $e) {
            return $e->errors[$file];
        }
    }

    public function test_groups_commitments_in_position_order_and_tallies(): void
    {
        $this->writeRace('leader', ['commitments' => [
            $this->commitmentData(['name' => 'Undecided One', 'position' => 'undecided']),
            $this->commitmentData(['name' => 'Later VH', 'date' => '2026-11-14']),
            $this->commitmentData(['name' => 'Early VH', 'date' => '2026-11-12']),
            $this->commitmentData(['name' => 'Refuser', 'position' => 'not-schumer']),
            $this->commitmentData(['name' => 'Backer', 'position' => 'schumer']),
            $this->commitmentData(['name' => 'Retiring Backer', 'position' => 'schumer', 'leaving' => true]),
        ]]);

        $race = app(RaceLoader::class)->load($this->contentDir.'/races')['leader'];

        $this->assertSame(['schumer', 'van-hollen', 'not-schumer', 'undecided'], array_column($race['groups'], 'position'));
        $this->assertSame(['For Schumer', 'For Van Hollen', 'Against Schumer, no pick yet', 'Undecided'], array_column($race['groups'], 'label'));
        $this->assertSame([1, 2, 1, 1], array_column($race['groups'], 'count'));
        $this->assertSame(['Early VH', 'Later VH'], array_column($race['groups'][1]['commitments'], 'name'));
        $this->assertSame(50, $race['unstated']);
        $this->assertTrue($race['groups'][0]['commitments'][1]['leaving']);
    }

    public function test_rejects_unknown_position(): void
    {
        $this->writeRace('leader', ['commitments' => [$this->commitmentData(['position' => 'durbin'])]]);

        $this->assertStringContainsString('unknown position "durbin"', $this->errorsFor('leader.yaml')[0]);
    }

    public function test_rejects_a_senator_listed_twice(): void
    {
        $this->writeRace('leader', ['commitments' => [
            $this->commitmentData(['name' => 'Same Senator']),
            $this->commitmentData(['name' => 'Same Senator', 'position' => 'schumer']),
        ]]);

        $this->assertStringContainsString('Same Senator', $this->errorsFor('leader.yaml')[0]);
    }

    public function test_rejects_unknown_related_entries(): void
    {
        $this->writeRace('leader', ['related_entries' => ['no-such-entry']]);

        $this->assertStringContainsString('no-such-entry', $this->errorsFor('leader.yaml', [])[0]);
    }

    public function test_needed_cannot_exceed_seats(): void
    {
        $this->writeRace('leader', ['seats' => 10, 'needed' => 11]);

        $this->assertNotEmpty($this->errorsFor('leader.yaml'));
    }

    public function test_commitment_requires_a_source(): void
    {
        $commitment = $this->commitmentData();
        unset($commitment['source']);
        $this->writeRace('leader', ['commitments' => [$commitment]]);

        $this->assertNotEmpty($this->errorsFor('leader.yaml'));
    }
}

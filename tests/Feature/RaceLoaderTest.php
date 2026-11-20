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

    public function test_log_lists_stances_and_changes_newest_first(): void
    {
        $earlier = ['position' => 'undecided', 'date' => '2026-11-10', 'source' => ['title' => 'Interview', 'outlet' => 'Outlet', 'url' => 'https://example.com/earlier', 'type' => 'article']];
        $this->writeRace('leader', ['commitments' => [
            $this->commitmentData(['name' => 'Switcher', 'position' => 'schumer', 'date' => '2026-11-14', 'previous' => [$earlier]]),
            $this->commitmentData(['name' => 'Steady', 'date' => '2026-11-12']),
        ]]);

        $log = app(RaceLoader::class)->load($this->contentDir.'/races')['leader']['log'];

        $this->assertSame(['Switcher', 'Steady', 'Switcher'], array_column($log, 'name'));
        $this->assertSame(['For Schumer', 'For Van Hollen', 'Undecided'], array_column($log, 'label'));
        $this->assertSame(['Undecided', null, null], array_column($log, 'from'));
        $this->assertSame('https://example.com/earlier', $log[2]['source_url']);
    }

    public function test_previous_position_must_be_known_and_earlier(): void
    {
        $source = ['title' => 'Interview', 'outlet' => 'Outlet', 'url' => 'https://example.com/a', 'type' => 'article'];
        $this->writeRace('leader', ['commitments' => [$this->commitmentData([
            'date' => '2026-11-12',
            'previous' => [
                ['position' => 'durbin', 'date' => '2026-11-10', 'source' => $source],
                ['position' => 'undecided', 'date' => '2026-11-13', 'source' => $source],
            ],
        ])]]);

        $errors = implode("\n", $this->errorsFor('leader.yaml'));

        $this->assertStringContainsString('previous.0: unknown position "durbin"', $errors);
        $this->assertStringContainsString('previous.1: dated after the current position', $errors);
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

    /**
     * @return array<string, string>
     */
    private function voter(string $name, string $state = 'VT'): array
    {
        return ['name' => $name, 'state' => $state, 'party' => 'D', 'phone' => '(202) 224-5141', 'contact_url' => 'https://example.senate.gov/contact'];
    }

    public function test_electorate_adds_contacts_and_lists_the_silent(): void
    {
        $this->writeRace('leader', [
            'electorate' => [$this->voter('Backer'), $this->voter('Quiet One', 'OR')],
            'commitments' => [
                $this->commitmentData(['name' => 'Backer', 'position' => 'schumer']),
                $this->commitmentData(['name' => 'New Member', 'incoming' => true]),
            ],
        ]);

        $race = app(RaceLoader::class)->load($this->contentDir.'/races')['leader'];

        $this->assertSame(['phone' => '(202) 224-5141', 'tel' => '+12022245141', 'url' => 'https://example.senate.gov/contact'], $race['groups'][0]['commitments'][0]['contact']);
        $this->assertNull($race['groups'][1]['commitments'][0]['contact']);
        $this->assertSame(['Quiet One'], array_column($race['silent'], 'name'));
        $this->assertSame('OR', $race['silent'][0]['state']);
    }

    public function test_without_electorate_nothing_is_silent(): void
    {
        $this->writeRace('leader', ['commitments' => [$this->commitmentData()]]);

        $race = app(RaceLoader::class)->load($this->contentDir.'/races')['leader'];

        $this->assertSame([], $race['silent']);
        $this->assertNull($race['groups'][0]['commitments'][0]['contact']);
    }

    public function test_sitting_senator_must_be_in_electorate(): void
    {
        $this->writeRace('leader', [
            'electorate' => [$this->voter('Someone Else')],
            'commitments' => [
                $this->commitmentData(['name' => 'Missing Senator']),
                $this->commitmentData(['name' => 'Retiring', 'leaving' => true]),
                $this->commitmentData(['name' => 'Elect', 'incoming' => true]),
            ],
        ]);

        $this->assertSame(['Not in the electorate: Missing Senator'], $this->errorsFor('leader.yaml'));
    }

    public function test_rejects_duplicate_electorate_and_bad_phone(): void
    {
        $this->writeRace('dupes', ['electorate' => [$this->voter('Twice'), $this->voter('Twice')]]);
        $this->assertSame(['Electorate lists more than once: Twice'], $this->errorsFor('dupes.yaml'));

        $this->tearDownContentDir();
        $this->setUpContentDir();
        $this->writeRace('phone', ['electorate' => [['phone' => '555-1234'] + $this->voter('Bad Phone')]]);
        $this->assertNotEmpty($this->errorsFor('phone.yaml'));
    }
}

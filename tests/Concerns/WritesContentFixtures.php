<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

trait WritesContentFixtures
{
    protected string $contentDir;

    protected function setUpContentDir(): void
    {
        $this->contentDir = sys_get_temp_dir().'/csfo-content-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($this->contentDir.'/policies');
        File::ensureDirectoryExists($this->contentDir.'/members');
        config([
            'csfo.content_path' => $this->contentDir,
            'csfo.policies_path' => $this->contentDir.'/policies',
            'csfo.members_path' => $this->contentDir.'/members',
            'csfo.highlights_path' => $this->contentDir.'/highlights.yaml',
        ]);
    }

    protected function tearDownContentDir(): void
    {
        File::deleteDirectory($this->contentDir);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function entryData(string $slug = 'sample-entry', array $overrides = []): array
    {
        if (($overrides['status'] ?? null) === 'needs-review') {
            $overrides += ['review_note' => 'Waiting on a second source.'];
        }

        return array_replace([
            'slug' => $slug,
            'title' => 'Sample entry',
            'date' => '2025-03-14',
            'category' => 'shutdowns-and-funding',
            'status' => 'verified',
            'summary' => 'Something happened.',
            'could_have' => 'Something else.',
            'context' => null,
            'schumer_response' => null,
            'sources' => [[
                'title' => 'Roll call vote',
                'outlet' => 'senate.gov',
                'url' => 'https://www.senate.gov/legislative/LIS/roll_call_votes/vote1191/vote_119_1_00133.htm',
                'date' => '2025-03-14',
                'type' => 'vote',
            ]],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function writePolicy(string $slug, array $overrides = []): string
    {
        if (($overrides['status'] ?? null) === 'needs-review') {
            $overrides += ['review_note' => 'Waiting on a second source.'];
        }

        $data = array_replace([
            'slug' => $slug,
            'title' => 'Sample policy',
            'tagline' => 'A short line.',
            'category' => 'healthcare',
            'status' => 'verified',
            'summary' => 'What it is.',
            'problem' => 'Why it matters.',
            'peers' => null,
            'record' => 'What leadership did.',
            'stats' => [['label' => 'A number', 'value' => '42%', 'as_of' => '2026', 'source_url' => 'https://example.com/data']],
            'polling' => [['pollster' => 'Pollster', 'date' => '2026-01-01', 'question' => 'Q?', 'result' => '60% support', 'url' => 'https://example.com/poll']],
            'related_entries' => [],
            'sources' => [['title' => 'Data', 'outlet' => 'Agency', 'url' => 'https://example.com/data', 'date' => '2026-01-01', 'type' => 'data']],
        ], $overrides);

        $path = "{$this->contentDir}/policies/{$slug}.yaml";
        File::put($path, Yaml::dump($data, 4));

        return $path;
    }

    /**
     * @param  array<string, mixed>|string  $data  array is dumped as YAML, string written raw
     */
    protected function writeEntry(string $slug, array|string $data = []): string
    {
        $path = "{$this->contentDir}/{$slug}.yaml";
        $yaml = is_string($data) ? $data : Yaml::dump($this->entryData($slug, $data), 4);
        File::put($path, $yaml);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function writeMember(string $slug, array $overrides = []): string
    {
        if (($overrides['status'] ?? null) === 'needs-review') {
            $overrides += ['review_note' => 'Waiting on a second source.'];
        }

        $data = array_replace([
            'slug' => $slug,
            'name' => 'Sample Member',
            'party' => 'D',
            'chamber' => 'senate',
            'state' => 'NY',
            'role' => 'Democratic Leader',
            'inclusion' => ['leadership'],
            'status' => 'verified',
            'summary' => 'Who they are.',
            'funding' => [['label' => 'Raised, career', 'value' => '$1M', 'as_of' => '2026', 'source_url' => 'https://example.com/fec']],
            'industries' => [['name' => 'Securities & investment', 'amount' => '$500K', 'period' => '2021-2026', 'source_url' => 'https://example.com/os']],
            'employers' => [['name' => 'Blackstone', 'amount' => '$100K', 'period' => '2021-22', 'source_url' => 'https://example.com/fec']],
            'votes' => [],
            'related_entries' => [],
            'sources' => [['title' => 'Data', 'outlet' => 'FEC', 'url' => 'https://example.com/fec', 'date' => '2026-01-01', 'type' => 'data']],
        ], $overrides);

        $path = "{$this->contentDir}/members/{$slug}.yaml";
        File::put($path, Yaml::dump($data, 4));

        return $path;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function voteData(array $overrides = []): array
    {
        return array_replace([
            'date' => '2025-03-14',
            'title' => 'Cloture on H.R. 1968',
            'position' => 'yea',
            'explanation' => 'What the vote did.',
            'url' => 'https://www.senate.gov/legislative/LIS/roll_call_votes/vote1191/vote_119_1_00133.htm',
            'policy' => null,
        ], $overrides);
    }
}

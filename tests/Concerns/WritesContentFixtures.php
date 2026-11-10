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
        File::ensureDirectoryExists($this->contentDir);
        config(['csfo.content_path' => $this->contentDir]);
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
     * @param  array<string, mixed>|string  $data  array is dumped as YAML, string written raw
     */
    protected function writeEntry(string $slug, array|string $data = []): string
    {
        $path = "{$this->contentDir}/{$slug}.yaml";
        $yaml = is_string($data) ? $data : Yaml::dump($this->entryData($slug, $data), 4);
        File::put($path, $yaml);

        return $path;
    }
}

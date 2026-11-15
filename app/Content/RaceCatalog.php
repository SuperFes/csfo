<?php

namespace App\Content;

/**
 * The races visible on the site, soonest election first.
 */
class RaceCatalog
{
    public function __construct(private RaceLoader $loader) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $races = array_filter(
            $this->loader->load(config('csfo.races_path')),
            fn (array $r) => $r['status'] === 'verified' || config('csfo.show_unverified'),
        );

        uasort($races, fn (array $a, array $b) => $a['election_date'] <=> $b['election_date']);

        return $races;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $slug): ?array
    {
        return $this->all()[$slug] ?? null;
    }
}

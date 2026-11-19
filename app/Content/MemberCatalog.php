<?php

namespace App\Content;

use App\Enums\Chamber;
use App\Enums\MemberInclusion;

/**
 * The members visible on the site: leadership first, then Senate before
 * House, then by name.
 */
class MemberCatalog
{
    public function __construct(private MemberLoader $loader) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $members = array_filter(
            $this->loader->load(config('csfo.members_path')),
            fn (array $m) => $m['status'] === 'verified' || config('csfo.show_unverified'),
        );

        $chambers = array_flip(array_map(fn (Chamber $c) => $c->value, Chamber::cases()));
        $rank = fn (array $m) => [
            in_array(MemberInclusion::Leadership->value, $m['inclusion'], true) ? 0 : 1,
            $chambers[$m['chamber']],
            $m['name'],
        ];

        uasort($members, fn (array $a, array $b) => $rank($a) <=> $rank($b));

        return $members;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $slug): ?array
    {
        return $this->all()[$slug] ?? null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function cards(): array
    {
        return array_values(array_map(fn (array $m) => $this->card($m), $this->all()));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function cardsForPolicy(string $policySlug): array
    {
        $related = array_filter(
            $this->all(),
            fn (array $m) => in_array($policySlug, array_column($m['votes'], 'policy'), true),
        );

        return array_values(array_map(fn (array $m) => $this->card($m), $related));
    }

    /**
     * The people an entry is about, in the entry's order, then anyone else
     * whose page points at it.
     *
     * @param  list<string>  $people
     * @return list<array<string, mixed>>
     */
    public function cardsForEntry(string $entrySlug, array $people = []): array
    {
        $all = $this->all();
        $subjects = array_values(array_filter(array_map(fn (string $slug) => $all[$slug] ?? null, $people)));
        $mentioned = array_filter(
            $all,
            fn (array $m) => ! in_array($m['slug'], $people, true) && in_array($entrySlug, $m['related_entries'], true),
        );

        return array_map(
            fn (array $m) => [...$this->card($m), 'subject' => in_array($m['slug'], $people, true)],
            [...$subjects, ...array_values($mentioned)],
        );
    }

    /**
     * Name lookup for everyone named in some entry's `people`.
     *
     * @return array<string, array{name: string, short_name: string}>
     */
    public function names(): array
    {
        return array_map(fn (array $m) => ['name' => $m['name'], 'short_name' => $m['short_name']], $this->all());
    }

    /**
     * @param  array<string, mixed>  $member
     * @return array<string, mixed>
     */
    private function card(array $member): array
    {
        return [
            'slug' => $member['slug'],
            'name' => $member['name'],
            'short_name' => $member['short_name'],
            'party' => $member['party'],
            'state' => $member['state'],
            'district' => $member['district'],
            'chamber_label' => $member['chamber_label'],
            'role' => $member['role'],
            'inclusion_labels' => $member['inclusion_labels'],
            'status' => $member['status'],
            'stat' => $member['funding'][0] ?? null,
        ];
    }
}

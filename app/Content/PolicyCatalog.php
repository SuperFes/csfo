<?php

namespace App\Content;

use App\Enums\PolicyCategory;

/**
 * The policies visible on the site, grouped by category in the enum's
 * declared order.
 */
class PolicyCatalog
{
    public function __construct(private PolicyLoader $loader) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $order = array_flip(array_map(fn (PolicyCategory $c) => $c->value, PolicyCategory::cases()));

        $policies = array_filter(
            $this->loader->load(config('csfo.policies_path')),
            fn (array $p) => $p['status'] === 'verified' || config('csfo.show_unverified'),
        );

        uasort($policies, fn (array $a, array $b) => [$order[$a['category']], $a['title']] <=> [$order[$b['category']], $b['title']]);

        return $policies;
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
        return array_values(array_map(fn (array $p) => $this->card($p), $this->all()));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function cardsForEntry(string $entrySlug): array
    {
        $related = array_filter($this->all(), fn (array $p) => in_array($entrySlug, $p['related_entries'], true));

        return array_values(array_map(fn (array $p) => $this->card($p), $related));
    }

    /**
     * @param  array<string, mixed>  $policy
     * @return array<string, mixed>
     */
    private function card(array $policy): array
    {
        return [
            'slug' => $policy['slug'],
            'title' => $policy['title'],
            'tagline' => $policy['tagline'],
            'category_label' => $policy['category_label'],
            'status' => $policy['status'],
            'stat' => $policy['stats'][0] ?? null,
        ];
    }
}

<?php

namespace App\Content;

use App\Enums\ResponseLevel;
use App\Models\Entry;
use Illuminate\Support\Collection;

/**
 * Groups tagged entries by how strongly leadership responded, so the page can
 * show every level, including the empty ones, which are the point.
 */
class ResponseLadder
{
    /**
     * @param  Collection<int, Entry>  $entries
     * @return array{total: int, actions: int, levels: list<array<string, mixed>>}
     */
    public static function build(Collection $entries, string $tag): array
    {
        $tagged = $entries
            ->filter(fn (Entry $e) => $e->response_level && in_array($tag, $e->tags ?? [], true))
            ->sortByDesc('date');

        $levels = array_map(function (ResponseLevel $level) use ($tagged) {
            $matches = $tagged->filter(fn (Entry $e) => $e->response_level === $level);

            return [
                'value' => $level->value,
                'label' => $level->label(),
                'description' => $level->description(),
                'action' => $level->isAction(),
                'entries' => $matches->map(fn (Entry $e) => [
                    'slug' => $e->slug,
                    'title' => $e->title,
                    'date' => $e->date->toDateString(),
                ])->values()->all(),
            ];
        }, ResponseLevel::cases());

        return [
            'total' => $tagged->count(),
            'actions' => $tagged->filter(fn (Entry $e) => $e->response_level->isAction())->count(),
            'levels' => $levels,
        ];
    }
}

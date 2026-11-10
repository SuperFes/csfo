<?php

namespace Tests\Unit;

use App\Content\ResponseLadder;
use App\Models\Entry;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ResponseLadderTest extends TestCase
{
    private function entry(string $slug, ?string $level, array $tags = ['retaliation'], string $date = '2025-06-12', string $status = 'verified'): Entry
    {
        return new Entry([
            'slug' => $slug,
            'status' => $status,
            'title' => ucfirst($slug),
            'date' => $date,
            'response_level' => $level,
            'tags' => $tags,
        ]);
    }

    public function test_lists_every_level_strongest_first_including_empty_ones(): void
    {
        $ladder = ResponseLadder::build(new Collection([$this->entry('a', 'statement')]), 'retaliation');

        $this->assertSame(
            ['leverage', 'procedural', 'floor-action', 'letter', 'statement', 'none'],
            array_column($ladder['levels'], 'value'),
        );
        $this->assertSame([], $ladder['levels'][0]['entries']);
    }

    public function test_counts_only_tagged_entries_with_a_level(): void
    {
        $ladder = ResponseLadder::build(new Collection([
            $this->entry('tagged', 'none'),
            $this->entry('untagged', 'none', []),
            $this->entry('other-tag', 'none', ['economy']),
            $this->entry('no-level', null),
        ]), 'retaliation');

        $this->assertSame(1, $ladder['total']);
        $this->assertSame(['tagged'], array_column($ladder['levels'][5]['entries'], 'slug'));
    }

    public function test_unverified_entries_are_left_out(): void
    {
        $ladder = ResponseLadder::build(new Collection([
            $this->entry('checked', 'none'),
            $this->entry('pending', 'none', status: 'needs-review'),
        ]), 'retaliation');

        $this->assertSame(1, $ladder['total']);
    }

    public function test_actions_count_only_levels_that_cost_something(): void
    {
        $ladder = ResponseLadder::build(new Collection([
            $this->entry('a', 'procedural'),
            $this->entry('b', 'floor-action'),
            $this->entry('c', 'letter'),
            $this->entry('d', 'statement'),
        ]), 'retaliation');

        $this->assertSame(4, $ladder['total']);
        $this->assertSame(2, $ladder['actions']);
    }

    public function test_entries_within_a_level_are_newest_first(): void
    {
        $ladder = ResponseLadder::build(new Collection([
            $this->entry('old', 'statement', date: '2025-01-01'),
            $this->entry('new', 'statement', date: '2026-01-01'),
        ]), 'retaliation');

        $this->assertSame(['new', 'old'], array_column($ladder['levels'][4]['entries'], 'slug'));
    }
}

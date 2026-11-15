<?php

namespace App\Content;

use App\Enums\EntryStatus;
use App\Enums\SourceType;
use Illuminate\Validation\Rule;

/**
 * Loads content/races/*.yaml: public commitments in a caucus leadership
 * race, grouped by position and tallied against the votes needed to win.
 */
class RaceLoader
{
    use ReadsYamlContent;

    public const UNDECIDED = 'undecided';

    /**
     * @param  list<string>|null  $entrySlugs  when given, related_entries must all exist
     * @return array<string, array<string, mixed>> keyed by slug, in file-name order
     *
     * @throws InvalidContentException
     */
    public function load(string $directory, ?array $entrySlugs = null): array
    {
        $errors = [];
        $races = [];

        foreach (glob(rtrim($directory, '/').'/*.yaml') ?: [] as $path) {
            $file = basename($path);

            try {
                $data = $this->readValidated($path, $this->rules());
            } catch (InvalidContentException $e) {
                $errors += $e->errors;

                continue;
            }

            if ($problems = $this->crossCheck($data, $entrySlugs)) {
                $errors[$file] = $problems;

                continue;
            }

            $races[$data['slug']] = $this->normalize($data);
        }

        if ($errors) {
            throw new InvalidContentException($errors);
        }

        return $races;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function crossCheck(array $data, ?array $entrySlugs): array
    {
        $problems = [];
        $positions = array_keys($this->positionLabels($data['candidates']));

        foreach ($data['commitments'] ?? [] as $i => $c) {
            if (! in_array($c['position'], $positions, true)) {
                $problems[] = "commitments.{$i}: unknown position \"{$c['position']}\".";
            }
        }

        $names = array_column($data['commitments'] ?? [], 'name');
        if ($dupes = array_unique(array_diff_assoc($names, array_unique($names)))) {
            $problems[] = 'Listed more than once: '.implode(', ', $dupes);
        }

        if ($entrySlugs !== null && $missing = array_diff($data['related_entries'] ?? [], $entrySlugs)) {
            $problems[] = 'Unknown related entries: '.implode(', ', $missing);
        }

        return $problems;
    }

    /**
     * Backing a candidate, refusing one without picking a side, or undecided,
     * in that display order.
     *
     * @param  list<array{key: string, name: string}>  $candidates
     * @return array<string, string>
     */
    private function positionLabels(array $candidates): array
    {
        $labels = [];

        foreach ($candidates as $c) {
            $labels[$c['key']] = 'For '.$c['name'];
        }

        foreach ($candidates as $c) {
            $labels['not-'.$c['key']] = 'Against '.$c['name'].', no pick yet';
        }

        return $labels + [self::UNDECIDED => 'Undecided'];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $commitments = array_map(fn (array $c) => [
            'name' => trim($c['name']),
            'state' => $c['state'],
            'party' => $c['party'],
            'incoming' => (bool) ($c['incoming'] ?? false),
            'leaving' => (bool) ($c['leaving'] ?? false),
            'position' => $c['position'],
            'date' => $c['date'],
            'quote' => $this->optionalText($c['quote'] ?? null),
            'source' => $this->normalizeSources([$c['source']])[0],
        ], $data['commitments'] ?? []);

        usort($commitments, fn (array $a, array $b) => [$a['date'], $a['name']] <=> [$b['date'], $b['name']]);

        $groups = [];
        foreach ($this->positionLabels($data['candidates']) as $position => $label) {
            $rows = array_values(array_filter($commitments, fn (array $c) => $c['position'] === $position));

            if ($rows) {
                $groups[] = [
                    'position' => $position,
                    'label' => $label,
                    // Senators leaving in January don't vote in the new caucus.
                    'count' => count(array_filter($rows, fn (array $c) => ! $c['leaving'])),
                    'commitments' => $rows,
                ];
            }
        }

        $counted = array_sum(array_column($groups, 'count'));

        return [
            'slug' => $data['slug'],
            'title' => trim($data['title']),
            'election_date' => $data['election_date'],
            'updated' => $data['updated'],
            'status' => $data['status'],
            'review_note' => $this->optionalText($data['review_note'] ?? null),
            'summary' => trim($data['summary']),
            'seats' => (int) $data['seats'],
            'needed' => (int) $data['needed'],
            'needed_note' => $this->optionalText($data['needed_note'] ?? null),
            'candidates' => array_map(fn (array $c) => ['key' => $c['key'], 'name' => trim($c['name'])], $data['candidates']),
            'groups' => $groups,
            'unstated' => max(0, (int) $data['seats'] - $counted),
            'related_entries' => array_values(array_unique($data['related_entries'] ?? [])),
            'sources' => $this->normalizeSources($data['sources']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:120'],
            'title' => ['required', 'string', 'max:160'],
            'election_date' => ['required', 'date_format:Y-m-d'],
            'updated' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', Rule::enum(EntryStatus::class)],
            'review_note' => ['required_if:status,needs-review', 'nullable', 'string'],
            'summary' => ['required', 'string'],
            'seats' => ['required', 'integer', 'min:1'],
            'needed' => ['required', 'integer', 'min:1', 'lte:seats'],
            'needed_note' => ['nullable', 'string'],
            'candidates' => ['required', 'array', 'min:2'],
            'candidates.*.key' => ['required', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'distinct'],
            'candidates.*.name' => ['required', 'string'],
            'commitments' => ['nullable', 'array'],
            'commitments.*.name' => ['required', 'string'],
            'commitments.*.state' => ['required', 'string', 'regex:/^[A-Z]{2}$/'],
            'commitments.*.party' => ['required', Rule::in(['D', 'I'])],
            'commitments.*.incoming' => ['nullable', 'boolean'],
            'commitments.*.leaving' => ['nullable', 'boolean'],
            'commitments.*.position' => ['required', 'string'],
            'commitments.*.date' => ['required', 'date_format:Y-m-d'],
            'commitments.*.quote' => ['nullable', 'string'],
            'commitments.*.source' => ['required', 'array'],
            'commitments.*.source.title' => ['required', 'string'],
            'commitments.*.source.outlet' => ['required', 'string'],
            'commitments.*.source.url' => ['required', 'url:https,http'],
            'commitments.*.source.date' => ['nullable', 'date_format:Y-m-d'],
            'commitments.*.source.type' => ['required', Rule::enum(SourceType::class)],
            'related_entries' => ['nullable', 'array'],
            'related_entries.*' => ['string'],
            ...$this->sourceRules(),
        ];
    }
}

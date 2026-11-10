<?php

namespace App\Content;

use App\Enums\EntryStatus;
use App\Enums\PolicyCategory;
use Illuminate\Validation\Rule;

/**
 * Loads content/policies/*.yaml. There are few enough policies that they're
 * read straight from disk; full-page caching keeps this off the hot path.
 */
class PolicyLoader
{
    use ReadsYamlContent;

    /**
     * @param  list<string>|null  $entrySlugs  when given, related_entries must all exist
     * @return array<string, array<string, mixed>> keyed by slug, in file-name order
     *
     * @throws InvalidContentException
     */
    public function load(string $directory, ?array $entrySlugs = null): array
    {
        $errors = [];
        $policies = [];

        foreach (glob(rtrim($directory, '/').'/*.yaml') ?: [] as $path) {
            try {
                $policy = $this->normalize($this->readValidated($path, $this->rules()));
            } catch (InvalidContentException $e) {
                $errors += $e->errors;

                continue;
            }

            if ($entrySlugs !== null && $missing = array_diff($policy['related_entries'], $entrySlugs)) {
                $errors[basename($path)] = ['Unknown related entries: '.implode(', ', $missing)];

                continue;
            }

            $policies[$policy['slug']] = $policy;
        }

        if ($errors) {
            throw new InvalidContentException($errors);
        }

        return $policies;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        return [
            'slug' => $data['slug'],
            'title' => trim($data['title']),
            'tagline' => trim($data['tagline']),
            'category' => $data['category'],
            'category_label' => PolicyCategory::from($data['category'])->label(),
            'status' => $data['status'],
            'review_note' => $this->optionalText($data['review_note'] ?? null),
            'summary' => trim($data['summary']),
            'problem' => trim($data['problem']),
            'peers' => $this->optionalText($data['peers'] ?? null),
            'record' => trim($data['record']),
            'stats' => array_map(fn (array $s) => [
                'label' => trim($s['label']),
                'value' => (string) $s['value'],
                'as_of' => (string) $s['as_of'],
                'source_url' => $s['source_url'],
            ], $data['stats'] ?? []),
            'polling' => array_map(fn (array $p) => [
                'pollster' => trim($p['pollster']),
                'date' => $p['date'],
                'question' => trim($p['question']),
                'result' => trim($p['result']),
                'url' => $p['url'],
            ], $data['polling'] ?? []),
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
            'title' => ['required', 'string', 'max:120'],
            'tagline' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::enum(PolicyCategory::class)],
            'status' => ['required', Rule::enum(EntryStatus::class)],
            'review_note' => ['required_if:status,needs-review', 'nullable', 'string'],
            'summary' => ['required', 'string'],
            'problem' => ['required', 'string'],
            'peers' => ['nullable', 'string'],
            'record' => ['required', 'string'],
            'stats' => ['nullable', 'array'],
            'stats.*.label' => ['required', 'string'],
            'stats.*.value' => ['required'],
            'stats.*.as_of' => ['required'],
            'stats.*.source_url' => ['required', 'url:https,http'],
            'polling' => ['nullable', 'array'],
            'polling.*.pollster' => ['required', 'string'],
            'polling.*.date' => ['required', 'date_format:Y-m-d'],
            'polling.*.question' => ['required', 'string'],
            'polling.*.result' => ['required', 'string'],
            'polling.*.url' => ['required', 'url:https,http'],
            'related_entries' => ['nullable', 'array'],
            'related_entries.*' => ['string'],
            ...$this->sourceRules(),
        ];
    }
}

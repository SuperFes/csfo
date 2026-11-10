<?php

namespace App\Content;

use App\Enums\Category;
use App\Enums\EntryStatus;
use App\Enums\ResponseLevel;
use App\Enums\SourceType;
use DateTimeInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Parses and validates one entry per YAML file. The file name (minus
 * extension) must match the entry's slug so URLs stay stable and greppable.
 */
class EntryParser
{
    /**
     * @return array<string, mixed> normalized attributes ready for Entry::fill()
     *
     * @throws InvalidContentException
     */
    public function parseFile(string $path): array
    {
        $file = basename($path);

        try {
            // Without PARSE_DATETIME, unquoted dates become Unix timestamps.
            $data = Yaml::parseFile($path, Yaml::PARSE_DATETIME);
        } catch (ParseException $e) {
            throw new InvalidContentException([$file => [$e->getMessage()]]);
        }

        return $this->parse($this->normalizeDates($data), pathinfo($path, PATHINFO_FILENAME), $file);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidContentException
     */
    public function parse(mixed $data, string $expectedSlug, string $file = '<input>'): array
    {
        if (! is_array($data)) {
            throw new InvalidContentException([$file => ['Expected a YAML mapping at the top level.']]);
        }

        $validator = Validator::make($data, $this->rules());
        $validator->after(function ($v) use ($data, $expectedSlug) {
            if (($data['slug'] ?? null) !== $expectedSlug) {
                $v->errors()->add('slug', "Slug must match file name \"{$expectedSlug}\".");
            }
        });

        if ($validator->fails()) {
            throw new InvalidContentException([$file => $validator->errors()->all()]);
        }

        return [
            'slug' => $data['slug'],
            'title' => trim($data['title']),
            'date' => $data['date'],
            'category' => $data['category'],
            'status' => $data['status'],
            'summary' => trim($data['summary']),
            'could_have' => trim($data['could_have']),
            'context' => $this->optionalText($data['context'] ?? null),
            'schumer_response' => $this->optionalText($data['schumer_response'] ?? null),
            'response_level' => $data['response_level'] ?? null,
            'tags' => array_values(array_unique($data['tags'] ?? [])),
            'sources' => array_map(fn (array $s) => [
                'title' => trim($s['title']),
                'outlet' => trim($s['outlet']),
                'url' => $s['url'],
                'date' => $s['date'] ?? null,
                'type' => $s['type'],
                'archive' => $s['archive'] ?? null,
            ], $data['sources']),
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
            'date' => ['required', 'date_format:Y-m-d'],
            'category' => ['required', Rule::enum(Category::class)],
            'status' => ['required', Rule::enum(EntryStatus::class)],
            'summary' => ['required', 'string'],
            'could_have' => ['required', 'string'],
            'context' => ['nullable', 'string'],
            'schumer_response' => ['nullable', 'string'],
            'response_level' => ['nullable', Rule::enum(ResponseLevel::class)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*.title' => ['required', 'string'],
            'sources.*.outlet' => ['required', 'string'],
            'sources.*.url' => ['required', 'url:https,http'],
            'sources.*.date' => ['nullable', 'date_format:Y-m-d'],
            'sources.*.type' => ['required', Rule::enum(SourceType::class)],
            'sources.*.archive' => ['nullable', 'url:https'],
        ];
    }

    private function normalizeDates(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_array($value)) {
            return array_map(fn ($v) => $this->normalizeDates($v), $value);
        }

        return $value;
    }

    private function optionalText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}

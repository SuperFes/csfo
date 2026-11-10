<?php

namespace App\Content;

use App\Enums\SourceType;
use DateTimeInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Shared loading and validation for one-record-per-file YAML content. The
 * file name (minus extension) must match the record's slug so URLs stay
 * stable and greppable.
 */
trait ReadsYamlContent
{
    /**
     * @return array<string, mixed> validated data with dates as Y-m-d strings
     *
     * @throws InvalidContentException
     */
    protected function readValidated(string $path, array $rules): array
    {
        $file = basename($path);

        try {
            // Without PARSE_DATETIME, unquoted dates become Unix timestamps.
            $data = $this->normalizeDates(Yaml::parseFile($path, Yaml::PARSE_DATETIME));
        } catch (ParseException $e) {
            throw new InvalidContentException([$file => [$e->getMessage()]]);
        }

        return $this->validated($data, $rules, pathinfo($path, PATHINFO_FILENAME), $file);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidContentException
     */
    protected function validated(mixed $data, array $rules, string $expectedSlug, string $file): array
    {
        if (! is_array($data)) {
            throw new InvalidContentException([$file => ['Expected a YAML mapping at the top level.']]);
        }

        $validator = Validator::make($data, $rules);
        $validator->after(function ($v) use ($data, $expectedSlug) {
            if (($data['slug'] ?? null) !== $expectedSlug) {
                $v->errors()->add('slug', "Slug must match file name \"{$expectedSlug}\".");
            }
        });

        if ($validator->fails()) {
            throw new InvalidContentException([$file => $validator->errors()->all()]);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function sourceRules(): array
    {
        return [
            'sources' => ['required', 'array', 'min:1'],
            'sources.*.title' => ['required', 'string'],
            'sources.*.outlet' => ['required', 'string'],
            'sources.*.url' => ['required', 'url:https,http'],
            'sources.*.date' => ['nullable', 'date_format:Y-m-d'],
            'sources.*.type' => ['required', Rule::enum(SourceType::class)],
            'sources.*.archive' => ['nullable', 'url:https'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sources
     * @return list<array<string, mixed>>
     */
    protected function normalizeSources(array $sources): array
    {
        return array_map(fn (array $s) => [
            'title' => trim($s['title']),
            'outlet' => trim($s['outlet']),
            'url' => $s['url'],
            'date' => $s['date'] ?? null,
            'type' => $s['type'],
            'archive' => $s['archive'] ?? null,
        ], $sources);
    }

    protected function normalizeDates(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_array($value)) {
            return array_map(fn ($v) => $this->normalizeDates($v), $value);
        }

        return $value;
    }

    protected function optionalText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}

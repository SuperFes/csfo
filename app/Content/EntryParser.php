<?php

namespace App\Content;

use App\Enums\Category;
use App\Enums\EntryStatus;
use App\Enums\ResponseLevel;
use Illuminate\Validation\Rule;

class EntryParser
{
    use ReadsYamlContent;

    /**
     * @return array<string, mixed> normalized attributes ready for Entry::fill()
     *
     * @throws InvalidContentException
     */
    public function parseFile(string $path): array
    {
        return $this->normalize($this->readValidated($path, $this->rules()));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidContentException
     */
    public function parse(mixed $data, string $expectedSlug, string $file = '<input>'): array
    {
        return $this->normalize($this->validated($data, $this->rules(), $expectedSlug, $file));
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
            'date' => $data['date'],
            'category' => $data['category'],
            'status' => $data['status'],
            'review_note' => $this->optionalText($data['review_note'] ?? null),
            'summary' => trim($data['summary']),
            'could_have' => trim($data['could_have']),
            'context' => $this->optionalText($data['context'] ?? null),
            'schumer_response' => $this->optionalText($data['schumer_response'] ?? null),
            'response_level' => $data['response_level'] ?? null,
            'tags' => array_values(array_unique($data['tags'] ?? [])),
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
            'date' => ['required', 'date_format:Y-m-d'],
            'category' => ['required', Rule::enum(Category::class)],
            'status' => ['required', Rule::enum(EntryStatus::class)],
            'review_note' => ['required_if:status,needs-review', 'nullable', 'string'],
            'summary' => ['required', 'string'],
            'could_have' => ['required', 'string'],
            'context' => ['nullable', 'string'],
            'schumer_response' => ['nullable', 'string'],
            'response_level' => ['nullable', Rule::enum(ResponseLevel::class)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            ...$this->sourceRules(),
        ];
    }
}

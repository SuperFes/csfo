<?php

namespace App\Content;

use DateTimeInterface;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class HighlightsLoader
{
    /**
     * @return list<array{value: string, label: string, source: array{outlet: string, title: string, url: string, date: string}}>
     *
     * @throws InvalidContentException
     */
    public function load(string $path): array
    {
        $file = basename($path);

        if (! is_file($path)) {
            return [];
        }

        try {
            $data = Yaml::parseFile($path, Yaml::PARSE_DATETIME);
        } catch (ParseException $e) {
            throw new InvalidContentException([$file => [$e->getMessage()]]);
        }

        $highlights = array_map(function ($h) {
            if (is_array($h) && ($h['source']['date'] ?? null) instanceof DateTimeInterface) {
                $h['source']['date'] = $h['source']['date']->format('Y-m-d');
            }

            return $h;
        }, $data['highlights'] ?? []);

        $validator = Validator::make(['highlights' => $highlights], [
            'highlights' => ['array'],
            'highlights.*.value' => ['required', 'string', 'max:12'],
            'highlights.*.label' => ['required', 'string', 'max:200'],
            'highlights.*.source.outlet' => ['required', 'string'],
            'highlights.*.source.title' => ['required', 'string'],
            'highlights.*.source.url' => ['required', 'url:https,http'],
            'highlights.*.source.date' => ['required', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            throw new InvalidContentException([$file => $validator->errors()->all()]);
        }

        return array_map(fn (array $h) => [
            'value' => $h['value'],
            'label' => $h['label'],
            'source' => [
                'outlet' => $h['source']['outlet'],
                'title' => $h['source']['title'],
                'url' => $h['source']['url'],
                'date' => $h['source']['date'],
            ],
        ], array_values($highlights));
    }
}

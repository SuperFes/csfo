<?php

namespace App\Content;

use App\Models\Entry;
use Illuminate\Support\Facades\DB;

/**
 * Mirrors content/entries/*.yaml into the entries table. All files are
 * validated before anything is written, so a bad file never leaves the
 * site half-updated.
 */
class ContentSync
{
    public function __construct(private EntryParser $parser) {}

    /**
     * @return array<string, array<string, mixed>> parsed entries keyed by slug
     *
     * @throws InvalidContentException
     */
    public function load(string $directory): array
    {
        $errors = [];
        $entries = [];

        foreach (glob(rtrim($directory, '/').'/*.{yaml,yml}', GLOB_BRACE) ?: [] as $path) {
            try {
                $entry = $this->parser->parseFile($path);
                $entries[$entry['slug']] = $entry;
            } catch (InvalidContentException $e) {
                $errors += $e->errors;
            }
        }

        if ($errors) {
            throw new InvalidContentException($errors);
        }

        ksort($entries);

        return $entries;
    }

    /**
     * @return array{created: int, updated: int, unchanged: int, deleted: int}
     *
     * @throws InvalidContentException
     */
    public function sync(string $directory): array
    {
        $entries = $this->load($directory);
        $stats = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'deleted' => 0];

        DB::transaction(function () use ($entries, &$stats) {
            foreach ($entries as $slug => $attributes) {
                $entry = Entry::firstOrNew(['slug' => $slug]);
                $entry->fill($attributes);

                $key = match (true) {
                    ! $entry->exists => 'created',
                    $entry->isDirty() => 'updated',
                    default => 'unchanged',
                };

                $entry->save();
                $stats[$key]++;
            }

            $stats['deleted'] = Entry::whereNotIn('slug', array_keys($entries))->delete();
        });

        return $stats;
    }
}

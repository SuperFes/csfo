<?php

namespace App\Console\Commands;

use App\Content\ContentSync;
use App\Content\HighlightsLoader;
use App\Content\InvalidContentException;
use App\Content\MemberLoader;
use App\Content\PolicyLoader;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\ResponseCache\Facades\ResponseCache;

#[Signature('content:sync {--check : Validate content without writing to the database}')]
#[Description('Validate all content and sync entries into the database')]
class SyncContent extends Command
{
    public function handle(ContentSync $sync, HighlightsLoader $highlights, PolicyLoader $policies, MemberLoader $members): int
    {
        $path = config('csfo.content_path');

        try {
            $highlights->load(config('csfo.highlights_path'));
            $entries = $sync->load($path);
            $policySlugs = array_keys($policies->load(config('csfo.policies_path'), array_keys($entries)));
            $memberCount = count($members->load(config('csfo.members_path'), array_keys($entries), $policySlugs));

            if ($this->option('check')) {
                $count = count($entries);
                $this->info("{$count} entries valid.");
                $this->info(count($policySlugs).' policies valid.');
                $this->info("{$memberCount} members valid.");

                return self::SUCCESS;
            }

            $stats = $sync->sync($path);
        } catch (InvalidContentException $e) {
            foreach ($e->errors as $file => $messages) {
                foreach ($messages as $message) {
                    $this->error("{$file}: {$message}");
                }
            }

            return self::FAILURE;
        }

        // Highlights aren't tracked in the database, so always start fresh.
        ResponseCache::clear();

        $this->info(vsprintf('created %d, updated %d, unchanged %d, deleted %d', array_values($stats)));

        return self::SUCCESS;
    }
}

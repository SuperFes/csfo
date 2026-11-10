<?php

namespace App\Console\Commands;

use App\Content\ContentSync;
use App\Content\InvalidContentException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\ResponseCache\Facades\ResponseCache;

#[Signature('content:sync {--check : Validate content without writing to the database}')]
#[Description('Validate content/entries/*.yaml and sync it into the database')]
class SyncContent extends Command
{
    public function handle(ContentSync $sync): int
    {
        $path = config('csfo.content_path');

        try {
            if ($this->option('check')) {
                $count = count($sync->load($path));
                $this->info("{$count} entries valid.");

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

        if ($stats['created'] || $stats['updated'] || $stats['deleted']) {
            ResponseCache::clear();
        }

        $this->info(vsprintf('created %d, updated %d, unchanged %d, deleted %d', array_values($stats)));

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('cdn:purge')]
#[Description('Purge every configured Cloudflare zone so edge caches pick up new content')]
class PurgeCdn extends Command
{
    public function handle(): int
    {
        $token = config('csfo.cloudflare.token');
        $zones = config('csfo.cloudflare.zones');

        if (! $token || ! $zones) {
            $this->warn('Cloudflare purge not configured; skipping.');

            return self::SUCCESS;
        }

        $failed = false;

        foreach ($zones as $zone) {
            $response = Http::withToken($token)
                ->timeout(15)
                ->post("https://api.cloudflare.com/client/v4/zones/{$zone}/purge_cache", ['purge_everything' => true]);

            if ($response->successful() && $response->json('success')) {
                $this->info("Purged zone {$zone}.");
            } else {
                $failed = true;
                $this->error("Purge failed for zone {$zone}: HTTP {$response->status()}");
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

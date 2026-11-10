<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PurgeCdnTest extends TestCase
{
    public function test_skips_when_not_configured(): void
    {
        Http::fake();
        config(['csfo.cloudflare' => ['token' => null, 'zones' => []]]);

        $this->artisan('cdn:purge')->expectsOutputToContain('skipping')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_purges_every_zone(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response(['success' => true])]);
        config(['csfo.cloudflare' => ['token' => 'secret', 'zones' => ['zone-a', 'zone-b']]]);

        $this->artisan('cdn:purge')->assertSuccessful();

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.cloudflare.com/client/v4/zones/zone-a/purge_cache'
            && $r['purge_everything'] === true
            && $r->hasHeader('Authorization', 'Bearer secret'));
    }

    public function test_fails_when_cloudflare_rejects(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response(['success' => false], 403)]);
        config(['csfo.cloudflare' => ['token' => 'bad', 'zones' => ['zone-a']]]);

        $this->artisan('cdn:purge')->expectsOutputToContain('HTTP 403')->assertFailed();
    }
}

<?php

namespace Tests\Unit;

use App\Support\Site;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SiteTest extends TestCase
{
    private const SITES = [
        'chuckschumerfuckoff.com' => 'Chuck Schumer Fuck Off',
        'chuckschumergoaway.com' => 'Chuck Schumer Go Away',
    ];

    public function test_canonical_host_uses_its_own_name_and_lists_the_other(): void
    {
        $site = Site::forHost('chuckschumergoaway.com', self::SITES, 'Default');

        $this->assertSame('Chuck Schumer Go Away', $site->name);
        $this->assertNull($site->redirectHost);
        $this->assertSame([['name' => 'Chuck Schumer Fuck Off', 'url' => 'https://chuckschumerfuckoff.com']], $site->alternates);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function aliases(): array
    {
        return [
            'www' => ['www.chuckschumerfuckoff.com', 'chuckschumerfuckoff.com'],
            'org' => ['chuckschumergoaway.org', 'chuckschumergoaway.com'],
            'www org' => ['www.chuckschumergoaway.org', 'chuckschumergoaway.com'],
            'mixed case' => ['ChuckSchumerFuckOff.ORG', 'chuckschumerfuckoff.com'],
        ];
    }

    #[DataProvider('aliases')]
    public function test_aliases_redirect_to_matching_canonical_host(string $host, string $canonical): void
    {
        $this->assertSame($canonical, Site::forHost($host, self::SITES, 'Default')->redirectHost);
    }

    public function test_unknown_host_uses_default_name_and_lists_every_site(): void
    {
        $site = Site::forHost('localhost', self::SITES, 'Default');

        $this->assertSame('Default', $site->name);
        $this->assertNull($site->redirectHost);
        $this->assertCount(2, $site->alternates);
    }
}

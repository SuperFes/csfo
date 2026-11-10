<?php

namespace App\Support;

/**
 * The site identity for a request's host. The same content is published
 * under more than one name.
 */
final readonly class Site
{
    /**
     * @param  list<array{name: string, url: string}>  $alternates
     */
    public function __construct(
        public string $name,
        public ?string $redirectHost,
        public array $alternates,
    ) {}

    /**
     * @param  array<string, string>  $sites  canonical host => site name
     */
    public static function forHost(string $host, array $sites, string $defaultName): self
    {
        $host = strtolower($host);
        $canonical = self::canonicalHost($host, $sites);

        $alternates = [];
        foreach ($sites as $siteHost => $name) {
            if ($siteHost !== $canonical) {
                $alternates[] = ['name' => $name, 'url' => "https://{$siteHost}"];
            }
        }

        return new self(
            name: $canonical ? $sites[$canonical] : $defaultName,
            redirectHost: $canonical && $canonical !== $host ? $canonical : null,
            alternates: $alternates,
        );
    }

    /**
     * @param  array<string, string>  $sites
     */
    private static function canonicalHost(string $host, array $sites): ?string
    {
        $bare = preg_replace('/^www\./', '', $host);

        foreach (array_keys($sites) as $siteHost) {
            $base = preg_replace('/\.com$/', '', $siteHost);

            if ($bare === $siteHost || $bare === "{$base}.org") {
                return $siteHost;
            }
        }

        return null;
    }
}

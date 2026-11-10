<?php

return [

    // Each canonical host gets its own site name. www. and .org variants
    // redirect to the matching canonical host.
    'sites' => [
        'chuckschumerfuckoff.com' => 'Chuck Schumer Fuck Off',
        'chuckschumergoaway.com' => 'Chuck Schumer Go Away',
    ],

    'content_path' => env('CSFO_CONTENT_PATH', base_path('content/entries')),

    'policies_path' => env('CSFO_POLICIES_PATH', base_path('content/policies')),

    'highlights_path' => env('CSFO_HIGHLIGHTS_PATH', base_path('content/highlights.yaml')),

    // Publish needs-review entries, labeled with their review note.
    'show_unverified' => (bool) env('CSFO_SHOW_UNVERIFIED', true),

    // Comma-separated proxy IPs/CIDRs, or "*". Only use "*" when the origin
    // firewall admits nothing but the CDN, otherwise clients can spoof IPs.
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    // Per-IP requests per minute reaching the origin.
    'rate_limit' => (int) env('CSFO_RATE_LIMIT', 120),

    // Browser and shared (CDN) cache lifetimes for public pages, in seconds.
    'cache' => [
        'max_age' => (int) env('CSFO_CACHE_MAX_AGE', 300),
        's_maxage' => (int) env('CSFO_CACHE_S_MAXAGE', 86400),
    ],

];

# Chuck Schumer Fuck Off

A sourced record of what Senate Democratic leadership could have done, and didn't.

Laravel 13, Inertia 3, Svelte 5, SQLite.

## Setup

```sh
composer setup          # install, .env, key, migrate, npm install, build
php artisan content:sync
composer dev
```

## Content

Each entry is one YAML file in `content/entries/`, named after its slug. Every entry needs at least one source.

```sh
php artisan content:sync --check   # validate only
php artisan content:sync           # validate, then sync to the database and clear the response cache
```

Entries with `status: needs-review` are hidden unless `CSFO_SHOW_UNVERIFIED=true`.

## Caching

Read-only pages (`routes/public.php`) are stateless: no session, cookies, or CSRF. They send `Cache-Control: public, s-maxage=…` so a CDN can cache them, and spatie/laravel-responsecache caches them at the origin. Inertia XHR responses are marked `private, no-store`.

Behind a CDN, set `TRUSTED_PROXIES` so rate limiting sees real client IPs, and firewall the origin to the CDN's ranges.

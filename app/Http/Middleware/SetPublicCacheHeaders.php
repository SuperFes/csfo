<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Support\Header;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks successful full-page GETs as shareable by the CDN. Inertia XHR
 * responses are JSON at the same URL, so they must never be stored by a
 * shared cache.
 */
class SetPublicCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethodCacheable() || ! $response->isSuccessful()) {
            return $response;
        }

        if ($request->hasHeader(Header::INERTIA)) {
            $response->headers->set('Cache-Control', 'private, no-store');

            return $response;
        }

        $response->setPublic();
        $response->setMaxAge(config('csfo.cache.max_age'));
        $response->setSharedMaxAge(config('csfo.cache.s_maxage'));

        return $response;
    }
}

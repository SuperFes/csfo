<?php

namespace App\Http\Middleware;

use App\Support\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveSite
{
    public function handle(Request $request, Closure $next): Response
    {
        $site = Site::forHost($request->getHost(), config('csfo.sites'), config('app.name'));

        if ($site->redirectHost) {
            return redirect()
                ->to("https://{$site->redirectHost}{$request->getRequestUri()}", 301)
                ->setPublic()
                ->setMaxAge(86400)
                ->setSharedMaxAge(86400);
        }

        config(['app.name' => $site->name]);
        app()->instance(Site::class, $site);

        return $next($request);
    }
}

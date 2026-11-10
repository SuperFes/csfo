<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($proxies = config('csfo.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : explode(',', $proxies));
        }

        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(config('csfo.rate_limit'))->by($request->ip()));
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $tenant = tenant();
            $key = $tenant ? $tenant->id : $request->ip();
            $limit = match ($tenant?->plan) {
                'enterprise' => 10000,
                'pro' => 3000,
                'starter' => 1000,
                default => 100,
            };

            return Limit::perMinute($limit)->by($key);
        });
    }
}

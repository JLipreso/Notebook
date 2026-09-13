<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        // Block migrate:fresh, db:wipe etc. in production (2026-09-12-002, §5).
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Laravel 12 has no RouteServiceProvider, so named limiters register
        // here. Applied per-route-group in routes/api.php:
        //   public reads  → throttle:public
        //   public writes → additionally throttle:public-write
        //   auth flows    → throttle:auth
        // Never key a limiter solely on client-supplied input (Exploria H-10).
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('public-write', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}

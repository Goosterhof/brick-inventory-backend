<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\BrickIdentificationServiceInterface;
use App\Contracts\LegoDataServiceInterface;
use App\Services\BrickognizeService;
use App\Services\RebrickableService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LegoDataServiceInterface::class, RebrickableService::class);
        $this->app->bind(BrickIdentificationServiceInterface::class, BrickognizeService::class);
        $this->app->bind(StatefulGuard::class, fn ($app) => Auth::guard('web'));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $enabled = (bool) config('app.rate_limit_enabled');

        RateLimiter::for('auth', fn (): Limit => $enabled
            ? Limit::perMinute(5)
            : Limit::none(),
        );

        RateLimiter::for('brick-identification', fn (): Limit => $enabled
            ? Limit::perMinute(10)
            : Limit::none(),
        );
    }
}

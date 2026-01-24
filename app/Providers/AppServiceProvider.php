<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\RebrickableServiceInterface;
use App\Services\RebrickableService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RebrickableServiceInterface::class, RebrickableService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\BrickIdentificationServiceInterface;
use App\Contracts\LegoDataServiceInterface;
use App\Services\BrickognizeService;
use App\Services\RebrickableService;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}

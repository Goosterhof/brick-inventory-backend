<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Routing Architecture
|--------------------------------------------------------------------------
|
| Routes must use `can:` middleware for authorization.
| This test requires the application to be booted to inspect routes.
|
*/

uses(TestCase::class);

it('should have can middleware on all authorized routes', function (): void {
    $routesThatRequireCanMiddleware = [
        // Storage Options
        ['GET', 'storage-options'],
        ['POST', 'storage-options'],
        ['GET', 'storage-options/{storage_option}'],
        ['PUT', 'storage-options/{storage_option}'],
        ['PATCH', 'storage-options/{storage_option}'],
        ['DELETE', 'storage-options/{storage_option}'],
        ['GET', 'storage-options/{storage_option}/parts'],
        ['POST', 'storage-options/{storage_option}/parts'],
        ['DELETE', 'storage-options/{storage_option}/parts/{storage_option_part}'],
        // Family Sets
        ['GET', 'family-sets'],
        ['POST', 'family-sets'],
        ['GET', 'family-sets/{family_set}'],
        ['PUT', 'family-sets/{family_set}'],
        ['PATCH', 'family-sets/{family_set}'],
        ['DELETE', 'family-sets/{family_set}'],
        ['POST', 'family-sets/import-from-rebrickable'],
        // Family
        ['GET', 'family/members'],
        ['GET', 'family/parts'],
        ['GET', 'family/stats'],
        ['PUT', 'family/rebrickable-token'],
        // Brick Identification
        ['POST', 'identify-brick'],
        // Sets
        ['GET', 'sets/{setNum}/parts'],
        ['GET', 'sets/ean/{ean}'],
        ['GET', 'sets/{setNum}/storage-map'],
    ];

    $routes = Route::getRoutes();

    foreach ($routesThatRequireCanMiddleware as [$method, $uri]) {
        $route = $routes->getRoutesByMethod()[$method]['api/' . $uri] ?? null;

        expect($route)->not->toBeNull(
            sprintf('Route %s %s should exist', $method, $uri),
        );

        $middleware = $route->gatherMiddleware();
        $hasCanMiddleware = false;

        foreach ($middleware as $m) {
            if (is_string($m) && str_starts_with($m, 'can:')) {
                $hasCanMiddleware = true;
                break;
            }
        }

        expect($hasCanMiddleware)->toBeTrue(
            sprintf('Route %s %s should have can: middleware for authorization', $method, $uri),
        );
    }
});

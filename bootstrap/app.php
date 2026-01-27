<?php

declare(strict_types=1);

use App\Exceptions\RebrickableApiException;
use App\Exceptions\SetNotFoundException;
use App\Http\Middleware\EnsureFamilyOwnership;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->alias([
            'family.ownership' => EnsureFamilyOwnership::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(fn (SetNotFoundException $setNotFoundException, Request $request): JsonResponse => response()->json(['error' => 'Set not found'], 404));

        $exceptions->render(function (RebrickableApiException $rebrickableApiException, Request $request): JsonResponse {
            $status = $rebrickableApiException->statusCode ?? 500;
            $message = match ($status) {
                401 => 'Invalid API key',
                default => 'Failed to fetch set data',
            };

            return response()->json(['error' => $message], $status);
        });
    })->create();

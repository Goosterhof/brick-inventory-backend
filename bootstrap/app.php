<?php

declare(strict_types=1);

use App\Exceptions\BrickognizeApiException;
use App\Exceptions\InvalidApiResponseException;
use App\Exceptions\MissingRebrickableTokenException;
use App\Exceptions\NotFamilyHeadException;
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
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        $middleware->alias([
            'family.ownership' => EnsureFamilyOwnership::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(fn (SetNotFoundException $setNotFoundException, Request $request): JsonResponse => response()->json(['error' => 'Set not found'], 404));

        $exceptions->render(fn (MissingRebrickableTokenException $missingRebrickableTokenException, Request $request): JsonResponse => response()->json(['error' => 'Rebrickable user token not configured'], 400));

        $exceptions->render(fn (NotFamilyHeadException $notFamilyHeadException, Request $request): JsonResponse => response()->json(['error' => 'Only the family head can perform this action'], 403));

        $exceptions->render(function (RebrickableApiException $rebrickableApiException, Request $request): JsonResponse {
            $message = match ($rebrickableApiException->statusCode) {
                401 => 'Invalid API key',
                default => 'Failed to fetch set data',
            };
            $status = match ($rebrickableApiException->statusCode) {
                404 => 404,
                default => 502,
            };

            return response()->json(['error' => $message], $status);
        });

        $exceptions->render(fn (BrickognizeApiException $brickognizeApiException, Request $request): JsonResponse => response()->json(['error' => 'Failed to identify brick'], 502));

        $exceptions->render(fn (InvalidApiResponseException $invalidApiResponseException, Request $request): JsonResponse => response()->json(['error' => 'Unexpected response from external API'], 502));
    })->create();

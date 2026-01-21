<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\FamilySetController;
use App\Http\Controllers\SetController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'message' => 'Welcome to the API',
]));

Route::post('/register', RegisterController::class);

Route::get('/sets/{setNum}/parts', [SetController::class, 'parts']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('family-sets', FamilySetController::class);
});

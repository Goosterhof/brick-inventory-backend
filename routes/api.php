<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\SetController;
use App\Http\Controllers\StorageOptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'message' => 'Welcome to the API',
]));

Route::post('/register', RegisterController::class);

Route::get('/sets/{setNum}/parts', [SetController::class, 'parts']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('storage-options', StorageOptionController::class);
    Route::get('/storage-options/{storage_option}/parts', [StorageOptionController::class, 'parts']);
    Route::post('/storage-options/{storage_option}/parts', [StorageOptionController::class, 'assignPart']);
    Route::delete('/storage-options/{storage_option}/parts/{part}', [StorageOptionController::class, 'removePart']);
});

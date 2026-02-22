<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BrickIdentificationController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\FamilySetController;
use App\Http\Controllers\SetController;
use App\Http\Controllers\StorageOptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'message' => 'Welcome to the API',
]));

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'timestamp' => now()->toIso8601String(),
]));

Route::post('/register', RegisterController::class)->middleware('throttle:5,1');
Route::post('/login', LoginController::class)->middleware('throttle:5,1');
Route::post('/logout', LogoutController::class)->middleware('auth:sanctum');
Route::get('/me', MeController::class)->middleware('auth:sanctum');

Route::get('/sets/{setNum}/parts', [SetController::class, 'parts'])
    ->where('setNum', '\d+-\d+')
    ->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'family.ownership'])->group(function (): void {
    Route::apiResource('storage-options', StorageOptionController::class);
    Route::get('/storage-options/{storage_option}/parts', [StorageOptionController::class, 'parts']);
    Route::post('/storage-options/{storage_option}/parts', [StorageOptionController::class, 'assignPart']);
    Route::delete('/storage-options/{storage_option}/parts/{storage_option_part}', [StorageOptionController::class, 'removePart'])
        ->scopeBindings();
    Route::apiResource('family-sets', FamilySetController::class);
    Route::post('/family-sets/import-from-rebrickable', [FamilySetController::class, 'importFromRebrickable']);
    Route::put('/family/rebrickable-token', [FamilyController::class, 'setRebrickableToken']);
    Route::post('/identify-brick', [BrickIdentificationController::class, 'identify'])
        ->middleware('throttle:10,1');
});

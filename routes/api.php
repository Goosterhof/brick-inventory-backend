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

Route::post('/register', RegisterController::class)->middleware('throttle:auth');
Route::post('/login', LoginController::class)->middleware('throttle:auth');
Route::post('/logout', LogoutController::class)->middleware('auth:sanctum');
Route::get('/me', MeController::class)->middleware('auth:sanctum');

Route::get('/sets/{setNum}/parts', [SetController::class, 'parts'])
    ->where('setNum', '\d+-\d+')
    ->middleware(['auth:sanctum', 'can:viewParts']);

Route::middleware(['auth:sanctum', 'family.ownership'])->group(function (): void {
    // Storage Options
    Route::get('/storage-options', [StorageOptionController::class, 'index'])
        ->middleware('can:viewAny,App\Models\StorageOption');
    Route::post('/storage-options', [StorageOptionController::class, 'store'])
        ->middleware('can:create,App\Models\StorageOption');
    Route::get('/storage-options/{storage_option}', [StorageOptionController::class, 'show'])
        ->middleware('can:view,storage_option');
    Route::put('/storage-options/{storage_option}', [StorageOptionController::class, 'update'])
        ->middleware('can:update,storage_option');
    Route::patch('/storage-options/{storage_option}', [StorageOptionController::class, 'update'])
        ->middleware('can:update,storage_option');
    Route::delete('/storage-options/{storage_option}', [StorageOptionController::class, 'destroy'])
        ->middleware('can:delete,storage_option');
    Route::get('/storage-options/{storage_option}/parts', [StorageOptionController::class, 'parts'])
        ->middleware('can:viewParts,storage_option');
    Route::post('/storage-options/{storage_option}/parts', [StorageOptionController::class, 'assignPart'])
        ->middleware('can:assignPart,storage_option');
    Route::delete('/storage-options/{storage_option}/parts/{storage_option_part}', [StorageOptionController::class, 'removePart'])
        ->scopeBindings()
        ->middleware('can:delete,storage_option_part');

    // Family Sets
    Route::get('/family-sets', [FamilySetController::class, 'index'])
        ->middleware('can:viewAny,App\Models\FamilySet');
    Route::post('/family-sets', [FamilySetController::class, 'store'])
        ->middleware('can:create,App\Models\FamilySet');
    Route::get('/family-sets/{family_set}', [FamilySetController::class, 'show'])
        ->middleware('can:view,family_set');
    Route::put('/family-sets/{family_set}', [FamilySetController::class, 'update'])
        ->middleware('can:update,family_set');
    Route::patch('/family-sets/{family_set}', [FamilySetController::class, 'update'])
        ->middleware('can:update,family_set');
    Route::delete('/family-sets/{family_set}', [FamilySetController::class, 'destroy'])
        ->middleware('can:delete,family_set');
    Route::post('/family-sets/import-from-rebrickable', [FamilySetController::class, 'importFromRebrickable'])
        ->middleware('can:importFromRebrickable,App\Models\FamilySet');

    // Family
    Route::get('/family/stats', [FamilyController::class, 'stats'])
        ->middleware('can:viewStats,App\Models\Family');
    Route::put('/family/rebrickable-token', [FamilyController::class, 'setRebrickableToken'])
        ->middleware('can:setRebrickableToken,App\Models\Family');

    // Brick Identification
    Route::post('/identify-brick', [BrickIdentificationController::class, 'identify'])
        ->middleware(['throttle:brick-identification', 'can:identify']);
});

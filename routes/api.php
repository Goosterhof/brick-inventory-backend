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
use App\Models\Family;
use App\Models\FamilySet;
use App\Models\StorageOption;
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
    ->middleware(['auth:sanctum', 'throttle:rebrickable'])
    ->can('viewParts');

Route::get('/sets/ean/{ean}', [SetController::class, 'lookupByEan'])
    ->where('ean', '\d{8,14}')
    ->middleware(['auth:sanctum', 'throttle:rebrickable'])
    ->can('lookupByEan');

Route::get('/sets/{setNum}/storage-map', [SetController::class, 'storageMap'])
    ->where('setNum', '\d+-\d+')
    ->middleware(['auth:sanctum', 'throttle:rebrickable'])
    ->can('viewStorageMap');

Route::middleware(['auth:sanctum', 'family.ownership'])->group(function (): void {
    // Storage Options
    Route::get('/storage-options', [StorageOptionController::class, 'index'])
        ->can('viewAny', StorageOption::class);
    Route::post('/storage-options', [StorageOptionController::class, 'store'])
        ->can('create', StorageOption::class);
    Route::get('/storage-options/{storage_option}', [StorageOptionController::class, 'show'])
        ->can('view', 'storage_option');
    Route::put('/storage-options/{storage_option}', [StorageOptionController::class, 'update'])
        ->can('update', 'storage_option');
    Route::patch('/storage-options/{storage_option}', [StorageOptionController::class, 'update'])
        ->can('update', 'storage_option');
    Route::delete('/storage-options/{storage_option}', [StorageOptionController::class, 'destroy'])
        ->can('delete', 'storage_option');
    Route::get('/storage-options/{storage_option}/parts', [StorageOptionController::class, 'parts'])
        ->can('viewParts', 'storage_option');
    Route::post('/storage-options/{storage_option}/parts', [StorageOptionController::class, 'assignPart'])
        ->can('assignPart', 'storage_option');
    Route::delete('/storage-options/{storage_option}/parts/{storage_option_part}', [StorageOptionController::class, 'removePart'])
        ->scopeBindings()
        ->can('delete', 'storage_option_part');

    // Family Sets
    Route::get('/family-sets', [FamilySetController::class, 'index'])
        ->can('viewAny', FamilySet::class);
    Route::post('/family-sets', [FamilySetController::class, 'store'])
        ->can('create', FamilySet::class);
    Route::get('/family-sets/{family_set}', [FamilySetController::class, 'show'])
        ->can('view', 'family_set');
    Route::put('/family-sets/{family_set}', [FamilySetController::class, 'update'])
        ->can('update', 'family_set');
    Route::patch('/family-sets/{family_set}', [FamilySetController::class, 'update'])
        ->can('update', 'family_set');
    Route::delete('/family-sets/{family_set}', [FamilySetController::class, 'destroy'])
        ->can('delete', 'family_set');
    Route::post('/family-sets/import-from-rebrickable', [FamilySetController::class, 'importFromRebrickable'])
        ->can('importFromRebrickable', FamilySet::class);

    // Family
    Route::get('/family/members', [FamilyController::class, 'members'])
        ->can('viewMembers', Family::class);
    Route::get('/family/parts', [FamilyController::class, 'parts'])
        ->can('viewParts', Family::class);
    Route::get('/family/stats', [FamilyController::class, 'stats'])
        ->can('viewStats', Family::class);
    Route::put('/family/rebrickable-token', [FamilyController::class, 'setRebrickableToken'])
        ->can('setRebrickableToken', Family::class);

    // Brick Identification
    Route::post('/identify-brick', [BrickIdentificationController::class, 'identify'])
        ->middleware('throttle:brick-identification')
        ->can('identify');
});

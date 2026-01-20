<?php

declare(strict_types=1);

use App\Http\Controllers\SetController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'message' => 'Welcome to the API',
]));

Route::get('/sets/{setNum}/parts', [SetController::class, 'parts']);

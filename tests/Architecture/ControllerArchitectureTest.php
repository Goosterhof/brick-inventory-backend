<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

arch('controllers should end with Controller')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('controllers should not use DB facade')
    ->expect('App\Http\Controllers')
    ->not->toUse(DB::class);

arch('controllers should not use Eloquent Builder directly')
    ->expect('App\Http\Controllers')
    ->not->toUse(Builder::class);

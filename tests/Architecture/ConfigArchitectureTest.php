<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;

arch('application classes must not use config() helper')
    ->expect('App')
    ->not->toUse('config')
    ->ignoring('App\Providers');

arch('application classes must not use Config facade')
    ->expect('App')
    ->not->toUse(Config::class)
    ->ignoring('App\Providers');

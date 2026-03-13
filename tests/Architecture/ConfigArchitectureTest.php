<?php

declare(strict_types=1);

arch('application classes must not use config() helper')
    ->expect('App')
    ->not->toUse('config')
    ->ignoring('App\Providers');

arch('application classes must not use Config facade')
    ->expect('App')
    ->not->toUse('Illuminate\Support\Facades\Config')
    ->ignoring('App\Providers');

<?php

declare(strict_types=1);

arch('no debugging statements')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'var_dump', 'ray']);

arch('all files should use strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('no env calls outside config')
    ->expect('App')
    ->not->toUse('env');

arch('no sleep calls in application code')
    ->expect('App')
    ->not->toUse('sleep');

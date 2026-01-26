<?php

declare(strict_types=1);

arch('services should end with Service')
    ->expect('App\Services')
    ->toHaveSuffix('Service');

arch('services should be final')
    ->expect('App\Services')
    ->toBeFinal();

arch('services should not extend anything')
    ->expect('App\Services')
    ->toExtendNothing();

arch('services should not depend on Actions')
    ->expect('App\Services')
    ->toUseNothing()
    ->ignoring([
        'App\Contracts',
        'App\Data',
        'App\Exceptions',
        'Illuminate',
    ]);

arch('services should not use Models directly')
    ->expect('App\Services')
    ->not->toUse('App\Models');

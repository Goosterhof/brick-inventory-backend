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

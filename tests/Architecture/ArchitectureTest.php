<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

arch('controllers should end with Controller')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('models should extend Illuminate\Database\Eloquent\Model')
    ->expect('App\Models')
    ->toExtend(Model::class);

arch('data transfer objects should end with Data')
    ->expect('App\DataTransferObjects')
    ->toHaveSuffix('Data');

arch('requests should end with Request')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request');

arch('services should end with Service')
    ->expect('App\Services')
    ->toHaveSuffix('Service');

arch('actions should end with Action')
    ->expect('App\Actions')
    ->toHaveSuffix('Action');

arch('no debugging statements')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'var_dump', 'ray']);

arch('all files should use strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('data transfer objects should be readonly')
    ->expect('App\DataTransferObjects')
    ->toBeReadonly();

arch('data transfer objects should be final')
    ->expect('App\DataTransferObjects')
    ->toBeFinal();

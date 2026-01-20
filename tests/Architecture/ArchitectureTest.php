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

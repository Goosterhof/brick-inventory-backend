<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\FormRequest;

arch('requests should end with Request')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request');

arch('requests should extend FormRequest')
    ->expect('App\Http\Requests')
    ->toExtend(FormRequest::class);

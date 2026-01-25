<?php

declare(strict_types=1);

arch('requests should end with Request')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request');

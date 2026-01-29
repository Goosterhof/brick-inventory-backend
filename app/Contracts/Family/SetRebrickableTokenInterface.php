<?php

declare(strict_types=1);

namespace App\Contracts\Family;

interface SetRebrickableTokenInterface
{
    public string $rebrickableUserToken { get; }
}

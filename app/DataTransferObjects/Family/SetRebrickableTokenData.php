<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Family;

final readonly class SetRebrickableTokenData
{
    public function __construct(
        public string $rebrickableUserToken,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

final readonly class AssignPartToStorageData
{
    public function __construct(
        public int $storageOptionId,
        public int $partId,
        public ?int $colorId,
        public int $quantity,
    ) {}
}

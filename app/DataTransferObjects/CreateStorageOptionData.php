<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

final readonly class CreateStorageOptionData
{
    public function __construct(
        public int $familyId,
        public string $name,
        public ?string $description,
        public ?int $parentId,
        public ?int $row,
        public ?int $column,
    ) {}
}

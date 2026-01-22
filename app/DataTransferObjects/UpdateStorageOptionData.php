<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

final readonly class UpdateStorageOptionData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?int $parentId,
        public ?int $row,
        public ?int $column,
    ) {}
}

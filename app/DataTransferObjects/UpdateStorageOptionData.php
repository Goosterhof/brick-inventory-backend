<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

final readonly class UpdateStorageOptionData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?int $parentId = null,
        public ?int $row = null,
        public ?int $column = null,
    ) {}
}

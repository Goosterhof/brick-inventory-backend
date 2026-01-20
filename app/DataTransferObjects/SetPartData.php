<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

readonly class SetPartData
{
    public function __construct(
        public string $partNum,
        public string $name,
        public ?string $category,
        public ?string $imageUrl,
        public ColorData $color,
        public int $quantity,
        public bool $isSpare,
        public ?string $elementId,
    ) {}
}

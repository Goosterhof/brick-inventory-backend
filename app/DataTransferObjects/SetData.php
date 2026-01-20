<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

readonly class SetData
{
    public function __construct(
        public string $setNum,
        public string $name,
        public ?int $year,
        public int|string|null $theme,
        public int $numParts,
        public ?string $imageUrl,
    ) {}
}

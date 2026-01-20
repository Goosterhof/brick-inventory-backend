<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

final readonly class ColorData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $rgb,
        public bool $isTransparent,
    ) {}
}

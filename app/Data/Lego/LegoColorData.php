<?php

declare(strict_types = 1);

namespace App\Data\Lego;

/**
 * DTO for LEGO color data from external APIs.
 */
final readonly class LegoColorData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $rgb,
        public bool $isTransparent,
    ) {}
}

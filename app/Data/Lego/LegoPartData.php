<?php

declare(strict_types=1);

namespace App\Data\Lego;

/**
 * DTO for LEGO part data from external APIs.
 */
final readonly class LegoPartData
{
    public function __construct(
        public string $partNum,
        public string $name,
        public ?int $categoryId,
        public ?string $imageUrl,
    ) {}
}

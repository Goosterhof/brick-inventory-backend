<?php

declare(strict_types=1);

namespace App\Data;

final readonly class FamilySetCompletionData
{
    public function __construct(
        public int $familySetId,
        public string $setNum,
        public ?int $totalParts,
        public ?int $storedParts,
        public ?float $percentage,
    ) {}
}

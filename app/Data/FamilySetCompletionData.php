<?php

declare(strict_types = 1);

namespace App\Data;

use App\Contracts\ResourceDataSourceInterface;

final readonly class FamilySetCompletionData implements ResourceDataSourceInterface
{
    public function __construct(
        public int $familySetId,
        public string $setNum,
        public ?int $totalParts,
        public ?int $storedParts,
        public ?float $percentage,
    ) {}
}

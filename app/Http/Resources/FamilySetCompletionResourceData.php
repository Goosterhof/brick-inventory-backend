<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Contracts\ResourceDataSourceInterface;
use App\DataTransferObjects\Result\FamilySet\FamilySetCompletionData;

/**
 * @extends ComputedResourceData<FamilySetCompletionData>
 */
final readonly class FamilySetCompletionResourceData extends ComputedResourceData
{
    public function __construct(
        public int $family_set_id,
        public string $set_num,
        public ?int $total_parts,
        public ?int $stored_parts,
        public ?float $percentage,
    ) {}

    /**
     * @param FamilySetCompletionData $resourceDataSource
     */
    public static function from(ResourceDataSourceInterface $resourceDataSource): static
    {
        return new self(
            family_set_id: $resourceDataSource->familySetId,
            set_num: $resourceDataSource->setNum,
            total_parts: $resourceDataSource->totalParts,
            stored_parts: $resourceDataSource->storedParts,
            percentage: $resourceDataSource->percentage,
        );
    }
}

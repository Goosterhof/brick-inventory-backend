<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Contracts\ResourceDataSourceInterface;
use App\Data\FamilyStatsData;

/**
 * @extends ComputedResourceData<FamilyStatsData>
 */
final readonly class FamilyStatsResourceData extends ComputedResourceData
{
    /**
     * @param array<string, int> $sets_by_status
     */
    public function __construct(
        public int $total_sets,
        public int $total_set_quantity,
        public array $sets_by_status,
        public int $total_storage_locations,
        public int $total_unique_parts,
        public int $total_parts_quantity,
    ) {}

    /**
     * @param FamilyStatsData $resourceDataSource
     */
    public static function from(ResourceDataSourceInterface $resourceDataSource): static
    {
        return new self(
            total_sets: $resourceDataSource->totalSets,
            total_set_quantity: $resourceDataSource->totalSetQuantity,
            sets_by_status: $resourceDataSource->setsByStatus,
            total_storage_locations: $resourceDataSource->totalStorageLocations,
            total_unique_parts: $resourceDataSource->totalUniqueParts,
            total_parts_quantity: $resourceDataSource->totalPartsQuantity,
        );
    }
}

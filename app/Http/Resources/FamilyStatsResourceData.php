<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DataTransferObjects\Family\FamilyStatsData;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ResourceData<Model>
 */
final readonly class FamilyStatsResourceData extends ResourceData
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
     * @param FamilyStatsData $model
     *
     * @phpstan-ignore method.childParameterType
     */
    public static function from($model): static
    {
        return new self(
            total_sets: $model->totalSets,
            total_set_quantity: $model->totalSetQuantity,
            sets_by_status: $model->setsByStatus,
            total_storage_locations: $model->totalStorageLocations,
            total_unique_parts: $model->totalUniqueParts,
            total_parts_quantity: $model->totalPartsQuantity,
        );
    }
}

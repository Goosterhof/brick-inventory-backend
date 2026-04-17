<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Contracts\ResourceDataSourceInterface;
use App\Data\FamilyMissingPartsData;

/**
 * @extends ComputedResourceData<FamilyMissingPartsData>
 */
final readonly class FamilyMissingPartsResourceData extends ComputedResourceData
{
    /**
     * @param list<array{
     *     part_num: string,
     *     color_id: int,
     *     part_name: string,
     *     color_name: string,
     *     color_hex: string,
     *     part_image_url: string|null,
     *     quantity_needed: int,
     *     quantity_stored: int,
     *     shortfall: int,
     *     needed_by_set_nums: list<string>,
     * }>           $shortfalls
     * @param list<string> $unknown_family_set_ids
     */
    public function __construct(
        public array $shortfalls,
        public array $unknown_family_set_ids,
    ) {}

    /**
     * @param FamilyMissingPartsData $resourceDataSource
     */
    public static function from(ResourceDataSourceInterface $resourceDataSource): static
    {
        return new self(
            shortfalls: $resourceDataSource->shortfalls,
            unknown_family_set_ids: $resourceDataSource->unknownFamilySetIds,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Data\BrickDnaData;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ResourceData<Model>
 */
final readonly class BrickDnaResourceData extends ResourceData
{
    /**
     * @param list<array{color_id: int, name: string, rgb: string, is_transparent: bool, total_quantity: int}> $top_colors
     * @param list<array{part_id: int, part_num: string, name: string, category: string|null, total_quantity: int}> $top_part_types
     * @param list<array{part_id: int, part_num: string, part_name: string, color_id: int|null, color_name: string|null, color_rgb: string|null, quantity: int}> $rarest_parts
     */
    public function __construct(
        public array $top_colors,
        public array $top_part_types,
        public array $rarest_parts,
        public float $diversity_score,
        public int $total_unique_parts,
        public int $total_parts_quantity,
    ) {}

    /**
     * @param BrickDnaData $model
     *
     * @phpstan-ignore method.childParameterType
     */
    public static function from($model): static
    {
        return new self(
            top_colors: $model->topColors,
            top_part_types: $model->topPartTypes,
            rarest_parts: $model->rarestParts,
            diversity_score: $model->diversityScore,
            total_unique_parts: $model->totalUniqueParts,
            total_parts_quantity: $model->totalPartsQuantity,
        );
    }
}

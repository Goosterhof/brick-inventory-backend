<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SetPart;

/**
 * @extends ResourceData<SetPart>
 */
final readonly class SetPartResourceData extends ResourceData
{
    public function __construct(
        public int $id,
        public int $part_id,
        public int $color_id,
        public int $quantity,
        public bool $is_spare,
        public ?string $element_id,
    ) {}

    /**
     * @param SetPart $model
     */
    public static function from($model): static
    {
        return new self(
            id: $model->id,
            part_id: $model->part_id,
            color_id: $model->color_id,
            quantity: $model->quantity,
            is_spare: $model->is_spare,
            element_id: $model->element_id,
        );
    }
}

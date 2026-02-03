<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StorageOptionPart;
use Carbon\Carbon;

/**
 * @extends ResourceData<StorageOptionPart>
 */
final readonly class StorageOptionPartResourceData extends ResourceData
{
    public function __construct(
        public int $id,
        public int $storage_option_id,
        public int $part_id,
        public ?int $color_id,
        public int $quantity,
        public ?Carbon $created_at,
        public ?Carbon $updated_at,
    ) {}

    /**
     * @param StorageOptionPart $model
     */
    public static function from($model): static
    {
        return new self(
            id: $model->id,
            storage_option_id: $model->storage_option_id,
            part_id: $model->part_id,
            color_id: $model->color_id,
            quantity: $model->quantity,
            created_at: $model->created_at,
            updated_at: $model->updated_at,
        );
    }
}

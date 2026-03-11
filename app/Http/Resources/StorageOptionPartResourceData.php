<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StorageOptionPart;

/**
 * @extends ResourceData<StorageOptionPart>
 */
final readonly class StorageOptionPartResourceData extends ResourceData
{
    public const EAGER_LOAD = ['part', 'color'];

    public function __construct(
        public int $id,
        public int $storage_option_id,
        public int $quantity,
        public PartResourceData $part,
        public ?ColorResourceData $color,
    ) {}

    /**
     * @param StorageOptionPart $model
     */
    public static function from($model): static
    {
        $model->loadMissing(self::requiredRelations());

        return new self(
            id: $model->id,
            storage_option_id: $model->storage_option_id,
            quantity: $model->quantity,
            part: PartResourceData::from($model->part),
            color: $model->color !== null ? ColorResourceData::from($model->color) : null,
        );
    }
}

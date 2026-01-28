<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StorageOptionPart;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

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
        public PartResourceData $part,
        public ?ColorResourceData $color,
        public ?Carbon $created_at,
        public ?Carbon $updated_at,
    ) {}

    public static function from(Model $model): static
    {
        $model->loadMissing(self::requiredRelations());

        return new self(
            id: $model->id,
            storage_option_id: $model->storage_option_id,
            part_id: $model->part_id,
            color_id: $model->color_id,
            quantity: $model->quantity,
            /** @phpstan-ignore argument.type (part relation must be loaded) */
            part: PartResourceData::from($model->part),
            color: $model->color !== null
                ? ColorResourceData::from($model->color)
                : null,
            created_at: $model->created_at,
            updated_at: $model->updated_at,
        );
    }

    protected static function requiredRelations(): array
    {
        return ['part', 'color'];
    }
}

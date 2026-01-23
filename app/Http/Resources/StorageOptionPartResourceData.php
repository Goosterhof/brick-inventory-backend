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
        public int $storageOptionId,
        public int $partId,
        public ?int $colorId,
        public int $quantity,
        public PartResourceData|MissingValue $part,
        public ColorResourceData|MissingValue|null $color,
        public ?Carbon $createdAt,
        public ?Carbon $updatedAt,
    ) {}

    public static function from(Model $model): static
    {
        /** @var StorageOptionPart $model */
        return new self(
            id: $model->id,
            storageOptionId: $model->storage_option_id,
            partId: $model->part_id,
            colorId: $model->color_id,
            quantity: $model->quantity,
            /** @phpstan-ignore argument.type (part is always set when part_id is required) */
            part: self::whenLoaded($model, 'part', fn (): PartResourceData => PartResourceData::from($model->part)),
            color: self::whenLoaded($model, 'color', fn (): ?ColorResourceData => $model->color !== null
                ? ColorResourceData::from($model->color)
                : null),
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}

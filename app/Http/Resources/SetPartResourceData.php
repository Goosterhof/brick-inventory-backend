<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Exceptions\MissingRelationException;
use App\Models\SetPart;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ResourceData<SetPart>
 */
final readonly class SetPartResourceData extends ResourceData
{
    public function __construct(
        public int $id,
        public int $quantity,
        public bool $is_spare,
        public ?string $element_id,
        public PartResourceData $part,
        public ColorResourceData $color,
    ) {}

    public static function from(Model $model): static
    {
        $model->loadMissing(self::requiredRelations());

        $part = $model->part;
        $color = $model->color;

        if ($part === null || $color === null) {
            $missing = array_filter(['part' => $part, 'color' => $color], fn ($v): bool => $v === null);

            throw MissingRelationException::forRelations(self::class, array_keys($missing));
        }

        return new self(
            id: $model->id,
            quantity: $model->quantity,
            is_spare: $model->is_spare,
            element_id: $model->element_id,
            part: PartResourceData::from($part),
            color: ColorResourceData::from($color),
        );
    }

    protected static function requiredRelations(): array
    {
        return ['part', 'color'];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Set;

/**
 * @extends ResourceData<Set>
 */
final readonly class SetWithPartsResourceData extends ResourceData
{
    /**
     * @param array<int, SetPartResourceData> $parts
     */
    public function __construct(
        public SetResourceData $set,
        public array $parts,
    ) {}

    /**
     * @param Set $model
     */
    public static function from($model): static
    {
        $model->loadMissing(self::requiredRelations());

        return new self(
            set: SetResourceData::from($model),
            parts: array_map(
                SetPartResourceData::from(...),
                $model->setParts->all(),
            ),
        );
    }

    protected static function requiredRelations(): array
    {
        return ['setParts.part', 'setParts.color'];
    }
}

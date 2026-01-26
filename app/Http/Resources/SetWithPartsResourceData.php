<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Set;
use Illuminate\Database\Eloquent\Model;

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

    public static function from(Model $model): static
    {
        return new self(
            set: SetResourceData::from($model),
            parts: array_map(
                SetPartResourceData::from(...),
                $model->setParts->all(),
            ),
        );
    }
}

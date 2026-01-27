<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\FamilySetStatus;
use App\Models\FamilySet;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ResourceData<FamilySet>
 */
final readonly class FamilySetResourceData extends ResourceData
{
    public function __construct(
        public int $id,
        public int $quantity,
        public FamilySetStatus $status,
        public ?string $purchase_date,
        public ?string $notes,
        public SetResourceData $set,
        public ?Carbon $created_at,
        public ?Carbon $updated_at,
    ) {}

    public static function from(Model $model): static
    {
        $model->loadMissing(self::requiredRelations());

        return new self(
            id: $model->id,
            quantity: $model->quantity,
            status: $model->status,
            purchase_date: $model->purchase_date?->format('Y-m-d'),
            notes: $model->notes,
            set: SetResourceData::from($model->set),
            created_at: $model->created_at,
            updated_at: $model->updated_at,
        );
    }

    protected static function requiredRelations(): array
    {
        return ['set'];
    }
}

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
        public ?string $purchaseDate,
        public ?string $notes,
        public SetResourceData $set,
        public ?Carbon $createdAt,
        public ?Carbon $updatedAt,
    ) {}

    public static function from(Model $model): static
    {
        /** @var FamilySet $model */
        return new self(
            id: $model->id,
            quantity: $model->quantity,
            status: $model->status,
            purchaseDate: $model->purchase_date?->format('Y-m-d'),
            notes: $model->notes,
            set: SetResourceData::from($model->set),
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}

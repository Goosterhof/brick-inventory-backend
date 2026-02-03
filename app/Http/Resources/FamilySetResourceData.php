<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\FamilySetStatus;
use App\Models\FamilySet;

/**
 * @extends ResourceData<FamilySet>
 */
final readonly class FamilySetResourceData extends ResourceData
{
    public function __construct(
        public int $id,
        public int $set_id,
        public int $quantity,
        public FamilySetStatus $status,
        public ?string $purchase_date,
        public ?string $notes,
    ) {}

    /**
     * @param FamilySet $model
     */
    public static function from($model): static
    {
        return new self(
            id: $model->id,
            set_id: $model->set_id,
            quantity: $model->quantity,
            status: $model->status,
            purchase_date: $model->purchase_date?->format('Y-m-d'),
            notes: $model->notes,
        );
    }
}

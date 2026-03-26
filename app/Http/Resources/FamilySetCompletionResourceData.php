<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Data\FamilySetCompletionData;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ResourceData<Model>
 */
final readonly class FamilySetCompletionResourceData extends ResourceData
{
    public function __construct(
        public int $family_set_id,
        public string $set_num,
        public ?int $total_parts,
        public ?int $stored_parts,
        public ?float $percentage,
    ) {}

    /**
     * @param FamilySetCompletionData $model
     *
     * @phpstan-ignore method.childParameterType
     */
    public static function from($model): static
    {
        return new self(
            family_set_id: $model->familySetId,
            set_num: $model->setNum,
            total_parts: $model->totalParts,
            stored_parts: $model->storedParts,
            percentage: $model->percentage,
        );
    }
}

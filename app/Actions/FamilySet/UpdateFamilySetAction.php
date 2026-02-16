<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\DataTransferObjects\FamilySet\UpdateFamilySetData;
use App\Models\FamilySet;
use DateTimeInterface;
use Illuminate\Support\DateFactory;

final readonly class UpdateFamilySetAction
{
    public function __construct(
        private DateFactory $dateFactory,
    ) {}

    public function execute(FamilySet $familySet, UpdateFamilySetData $updateFamilySetData): FamilySet
    {
        $familySet->quantity = $updateFamilySetData->quantity;
        $familySet->status = $updateFamilySetData->status;
        $familySet->purchase_date = $updateFamilySetData->purchaseDate instanceof DateTimeInterface
            ? $this->dateFactory->instance($updateFamilySetData->purchaseDate)
            : null;
        $familySet->notes = $updateFamilySetData->notes;
        $familySet->save();

        return $familySet;
    }
}

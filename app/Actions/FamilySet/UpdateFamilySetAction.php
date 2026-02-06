<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\Contracts\FamilySet\UpdateFamilySetInterface;
use App\Models\FamilySet;
use DateTimeInterface;
use Illuminate\Support\DateFactory;

final readonly class UpdateFamilySetAction
{
    public function __construct(
        private DateFactory $dateFactory,
    ) {}

    public function execute(FamilySet $familySet, UpdateFamilySetInterface $updateFamilySet): FamilySet
    {
        $familySet->quantity = $updateFamilySet->quantity;
        $familySet->status = $updateFamilySet->status;
        $familySet->purchase_date = $updateFamilySet->purchaseDate instanceof DateTimeInterface
            ? $this->dateFactory->instance($updateFamilySet->purchaseDate)
            : null;
        $familySet->notes = $updateFamilySet->notes;
        $familySet->save();

        return $familySet;
    }
}

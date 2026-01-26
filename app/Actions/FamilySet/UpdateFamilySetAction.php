<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\Contracts\FamilySet\UpdateFamilySetInterface;
use App\Models\FamilySet;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;

class UpdateFamilySetAction
{
    public function execute(FamilySet $familySet, UpdateFamilySetInterface $updateFamilySet): FamilySet
    {
        $familySet->quantity = $updateFamilySet->quantity;
        $familySet->status = $updateFamilySet->status;
        $familySet->purchase_date = $updateFamilySet->purchaseDate instanceof DateTimeInterface
            ? Date::instance($updateFamilySet->purchaseDate)
            : null;
        $familySet->notes = $updateFamilySet->notes;
        $familySet->save();

        return $familySet;
    }
}

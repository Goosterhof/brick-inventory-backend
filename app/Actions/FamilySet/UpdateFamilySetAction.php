<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\DataTransferObjects\UpdateFamilySetData;
use App\Models\FamilySet;
use Carbon\Carbon;
use DateTimeInterface;

class UpdateFamilySetAction
{
    public function execute(FamilySet $familySet, UpdateFamilySetData $data): FamilySet
    {
        $familySet->quantity = $data->quantity;
        $familySet->status = $data->status;
        $familySet->purchase_date = $data->purchaseDate instanceof DateTimeInterface
            ? Carbon::instance($data->purchaseDate)
            : null;
        $familySet->notes = $data->notes;
        $familySet->save();

        return $familySet;
    }
}

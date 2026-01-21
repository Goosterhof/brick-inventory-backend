<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\DataTransferObjects\UpdateFamilySetData;
use App\Enums\FamilySetStatus;
use App\Models\FamilySet;
use Carbon\Carbon;
use DateTimeInterface;

class UpdateFamilySetAction
{
    public function execute(FamilySet $familySet, UpdateFamilySetData $data): FamilySet
    {
        if ($data->quantity !== null) {
            $familySet->quantity = $data->quantity;
        }

        if ($data->status instanceof FamilySetStatus) {
            $familySet->status = $data->status;
        }

        if ($data->purchaseDate instanceof DateTimeInterface) {
            $familySet->purchase_date = Carbon::instance($data->purchaseDate);
        }

        if ($data->notes !== null) {
            $familySet->notes = $data->notes;
        }

        $familySet->save();

        return $familySet;
    }
}

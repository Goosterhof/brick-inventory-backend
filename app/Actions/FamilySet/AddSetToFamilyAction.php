<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\DataTransferObjects\CreateFamilySetData;
use App\Models\Family;
use App\Models\FamilySet;
use App\Services\RebrickableService;
use Carbon\Carbon;
use DateTimeInterface;

class AddSetToFamilyAction
{
    public function __construct(
        private readonly RebrickableService $rebrickableService,
    ) {}

    public function execute(Family $family, CreateFamilySetData $data): FamilySet
    {
        $set = $this->rebrickableService->getSet($data->setNum);

        $familySet = new FamilySet;
        $familySet->family_id = $family->id;
        $familySet->set_id = $set->id;
        $familySet->quantity = $data->quantity;
        $familySet->status = $data->status;
        $familySet->purchase_date = $data->purchaseDate instanceof DateTimeInterface ? Carbon::instance($data->purchaseDate) : null;
        $familySet->notes = $data->notes;
        $familySet->save();

        $familySet->load('set');

        return $familySet;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\Actions\GetSetAction;
use App\DataTransferObjects\CreateFamilySetData;
use App\DataTransferObjects\UpdateFamilySetData;
use App\Models\Family;
use App\Models\FamilySet;

class AddSetToFamilyAction
{
    public function __construct(
        private readonly GetSetAction $getSetAction,
        private readonly UpdateFamilySetAction $updateFamilySetAction,
        private readonly FamilySet $familySetModel,
    ) {}

    public function execute(Family $family, CreateFamilySetData $data): FamilySet
    {
        $set = $this->getSetAction->execute($data->setNum);

        /** @var FamilySet $familySet */
        $familySet = $this->familySetModel->newInstance();
        $familySet->family_id = $family->id;
        $familySet->set_id = $set->id;
        $familySet->save();

        $updateData = new UpdateFamilySetData(
            quantity: $data->quantity,
            status: $data->status,
            purchaseDate: $data->purchaseDate,
            notes: $data->notes,
        );

        $familySet = $this->updateFamilySetAction->execute($familySet, $updateData);
        $familySet->load('set');

        return $familySet;
    }
}

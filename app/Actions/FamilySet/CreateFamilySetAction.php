<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\Actions\GetSetAction;
use App\Contracts\FamilySet\CreateFamilySetInterface;
use App\Models\Family;
use App\Models\FamilySet;

class CreateFamilySetAction
{
    public function __construct(
        private readonly GetSetAction $getSetAction,
        private readonly UpdateFamilySetAction $updateFamilySetAction,
        private readonly FamilySet $familySetModel,
    ) {}

    public function execute(Family $family, CreateFamilySetInterface $data): FamilySet
    {
        $set = $this->getSetAction->execute($data->setNum);

        /** @var FamilySet $familySet */
        $familySet = $this->familySetModel->newInstance();
        $familySet->family_id = $family->id;
        $familySet->set_id = $set->id;
        $familySet->save();

        $familySet = $this->updateFamilySetAction->execute($familySet, $data);
        $familySet->load('set');

        return $familySet;
    }
}

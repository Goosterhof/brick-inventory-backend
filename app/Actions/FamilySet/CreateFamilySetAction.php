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
        private readonly FamilySet $familySet,
    ) {}

    public function execute(Family $family, CreateFamilySetInterface $createFamilySet): FamilySet
    {
        $set = $this->getSetAction->execute($createFamilySet->setNum);

        /** @var FamilySet $familySet */
        $familySet = $this->familySet->newInstance();
        $familySet->family_id = $family->id;
        $familySet->set_id = $set->id;
        $familySet->save();

        $familySet = $this->updateFamilySetAction->execute($familySet, $createFamilySet);
        $familySet->load('set');

        return $familySet;
    }
}

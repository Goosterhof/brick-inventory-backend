<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\Actions\GetSetAction;
use App\Contracts\FamilySet\CreateFamilySetInterface;
use App\Models\Family;
use App\Models\FamilySet;

final readonly class CreateFamilySetAction
{
    public function __construct(
        private GetSetAction $getSetAction,
        private UpdateFamilySetAction $updateFamilySetAction,
        private FamilySet $familySet,
    ) {}

    public function execute(Family $family, CreateFamilySetInterface $createFamilySet): FamilySet
    {
        $set = $this->getSetAction->execute($createFamilySet->setNum);

        /** @var FamilySet $familySet */
        $familySet = $this->familySet->newInstance();
        $familySet->family_id = $family->id;
        $familySet->set_id = $set->id;
        $familySet->save();

        return $this->updateFamilySetAction->execute($familySet, $createFamilySet);
    }
}

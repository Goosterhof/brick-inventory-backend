<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\Actions\GetSetAction;
use App\Contracts\FamilySet\CreateFamilySetInterface;
use App\Models\Family;
use App\Models\FamilySet;

class AddSetToFamilyAction
{
    public function __construct(
        private readonly GetSetAction $getSetAction,
        private readonly UpdateFamilySetAction $updateFamilySetAction,
    ) {}

    public function execute(Family $family, CreateFamilySetInterface $data): FamilySet
    {
        $set = $this->getSetAction->execute($data->setNum);

        /** @var FamilySet $familySet */
        $familySet = $family->familySets()->create([
            'set_id' => $set->id,
        ]);

        $familySet = $this->updateFamilySetAction->execute($familySet, $data);
        $familySet->load('set');

        return $familySet;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\DataTransferObjects\CreateFamilySetData;
use App\DataTransferObjects\UpdateFamilySetData;
use App\Models\Family;
use App\Models\FamilySet;
use App\Services\RebrickableService;

class AddSetToFamilyAction
{
    public function __construct(
        private readonly RebrickableService $rebrickableService,
        private readonly UpdateFamilySetAction $updateFamilySetAction,
    ) {}

    public function execute(Family $family, CreateFamilySetData $data): FamilySet
    {
        $set = $this->rebrickableService->getSet($data->setNum);

        /** @var FamilySet $familySet */
        $familySet = $family->familySets()->create([
            'set_id' => $set->id,
        ]);

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

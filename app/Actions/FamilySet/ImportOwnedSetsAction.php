<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\Actions\Sync\UpsertSetAction;
use App\Contracts\LegoDataServiceInterface;
use App\Data\ImportOwnedSetsResultData;
use App\Enums\FamilySetStatus;
use App\Exceptions\MissingRebrickableTokenException;
use App\Models\Family;
use App\Models\FamilySet;

class ImportOwnedSetsAction
{
    public function __construct(
        private readonly LegoDataServiceInterface $legoDataService,
        private readonly UpsertSetAction $upsertSetAction,
        private readonly FamilySet $familySet,
    ) {}

    /**
     * @throws MissingRebrickableTokenException
     */
    public function execute(Family $family): ImportOwnedSetsResultData
    {
        if ($family->rebrickable_user_token === null) {
            throw MissingRebrickableTokenException::forFamily($family->id);
        }

        $userSets = $this->legoDataService->fetchUserSets($family->rebrickable_user_token);

        $created = 0;
        $updated = 0;

        foreach ($userSets as $userSet) {
            $set = $this->upsertSetAction->execute($userSet->set);

            $familySet = $this->familySet->newQuery()
                ->where('family_id', $family->id)
                ->where('set_id', $set->id)
                ->first();

            if ($familySet instanceof FamilySet) {
                $familySet->quantity = $userSet->quantity;
                $familySet->save();
                $updated++;
            } else {
                /** @var FamilySet $familySet */
                $familySet = $this->familySet->newInstance();
                $familySet->family_id = $family->id;
                $familySet->set_id = $set->id;
                $familySet->quantity = $userSet->quantity;
                $familySet->status = FamilySetStatus::Sealed;
                $familySet->save();
                $created++;
            }
        }

        return new ImportOwnedSetsResultData(
            created: $created,
            updated: $updated,
            total: $created + $updated,
        );
    }
}

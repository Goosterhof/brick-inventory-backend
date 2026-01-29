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
use Illuminate\Support\Facades\DB;

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

        if ($userSets === []) {
            return new ImportOwnedSetsResultData(
                created: 0,
                updated: 0,
                skipped: 0,
                total: 0,
            );
        }

        // Wrap all database operations in a transaction for atomicity
        return DB::transaction(function () use ($family, $userSets): ImportOwnedSetsResultData {
            // First pass: Upsert all sets and build a map of set_num -> Set
            $setsByNum = [];
            foreach ($userSets as $userSet) {
                $setsByNum[$userSet->set->setNum] = $this->upsertSetAction->execute($userSet->set);
            }

            // Preload all existing FamilySets for this family in a single query
            $setIds = array_values(array_map(fn ($set) => $set->id, $setsByNum));
            $existingFamilySets = $this->familySet->newQuery()
                ->where('family_id', $family->id)
                ->whereIn('set_id', $setIds)
                ->get();

            // Group by set_id to detect duplicates
            /** @var array<int, array<FamilySet>> $familySetsBySetId */
            $familySetsBySetId = [];
            foreach ($existingFamilySets as $familySet) {
                $familySetsBySetId[$familySet->set_id][] = $familySet;
            }

            // Second pass: Create/update/skip based on the preloaded data
            $created = 0;
            $updated = 0;
            $skipped = 0;
            /** @var array<string> $skippedSetNums */
            $skippedSetNums = [];

            foreach ($userSets as $userSet) {
                $set = $setsByNum[$userSet->set->setNum];
                $existingForSet = $familySetsBySetId[$set->id] ?? [];
                $existingCount = count($existingForSet);

                if ($existingCount > 1) {
                    // Multiple rows exist for this set - skip to avoid inconsistent updates
                    $skipped++;
                    $skippedSetNums[] = $userSet->set->setNum;
                } elseif ($existingCount === 1) {
                    // Exactly one row exists - safe to update
                    $existingForSet[0]->quantity = $userSet->quantity;
                    $existingForSet[0]->save();
                    $updated++;
                } else {
                    // No existing rows - create new
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
                skipped: $skipped,
                total: $created + $updated,
                skippedSetNums: $skippedSetNums,
            );
        });
    }
}

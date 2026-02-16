<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\Actions\Sync\UpsertSetAction;
use App\Contracts\LegoDataServiceInterface;
use App\Data\ImportOwnedSetsResultData;
use App\Data\Lego\RebrickableUserSetData;
use App\Enums\FamilySetStatus;
use App\Exceptions\MissingRebrickableTokenException;
use App\Models\Family;
use App\Models\FamilySet;
use App\Models\Set;
use Illuminate\Database\ConnectionInterface;

final readonly class ImportOwnedSetsAction
{
    public function __construct(
        private LegoDataServiceInterface $legoDataService,
        private UpsertSetAction $upsertSetAction,
        private FamilySet $familySet,
        private ConnectionInterface $connection,
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

        return $this->connection->transaction(fn (): ImportOwnedSetsResultData => $this->importSets($family, $userSets));
    }

    /**
     * @param array<RebrickableUserSetData> $userSets
     */
    private function importSets(Family $family, array $userSets): ImportOwnedSetsResultData
    {
        $setsByNum = $this->upsertSetsFromUserData($userSets);
        $familySetsBySetId = $this->loadExistingFamilySetsGroupedBySetId($family, $setsByNum);

        return $this->processUserSets($family, $userSets, $setsByNum, $familySetsBySetId);
    }

    /**
     * @param array<RebrickableUserSetData> $userSets
     *
     * @return array<string, Set>
     */
    private function upsertSetsFromUserData(array $userSets): array
    {
        $setsByNum = [];

        foreach ($userSets as $userSet) {
            $setsByNum[$userSet->set->setNum] = $this->upsertSetAction->execute($userSet->set);
        }

        return $setsByNum;
    }

    /**
     * @param array<string, Set> $setsByNum
     *
     * @return array<int, array<FamilySet>>
     */
    private function loadExistingFamilySetsGroupedBySetId(Family $family, array $setsByNum): array
    {
        $setIds = array_values(array_map(fn (Set $set) => $set->id, $setsByNum));

        $existingFamilySets = $this->familySet->newQuery()
            ->where('family_id', $family->id)
            ->whereIn('set_id', $setIds)
            ->get();

        $familySetsBySetId = [];

        foreach ($existingFamilySets as $existingFamilySet) {
            $familySetsBySetId[$existingFamilySet->set_id][] = $existingFamilySet;
        }

        return $familySetsBySetId;
    }

    /**
     * @param array<RebrickableUserSetData> $userSets
     * @param array<string, Set> $setsByNum
     * @param array<int, array<FamilySet>> $familySetsBySetId
     */
    private function processUserSets(
        Family $family,
        array $userSets,
        array $setsByNum,
        array $familySetsBySetId,
    ): ImportOwnedSetsResultData {
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
                $skipped++;
                $skippedSetNums[] = $userSet->set->setNum;
            } elseif ($existingCount === 1) {
                $this->updateExistingFamilySet($existingForSet[0], $userSet->quantity);
                $updated++;
            } else {
                $this->createFamilySet($family, $set, $userSet->quantity);
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
    }

    private function updateExistingFamilySet(FamilySet $familySet, int $quantity): void
    {
        $familySet->quantity = $quantity;
        $familySet->save();
    }

    private function createFamilySet(Family $family, Set $set, int $quantity): void
    {
        /** @var FamilySet $familySet */
        $familySet = $this->familySet->newInstance();
        $familySet->family_id = $family->id;
        $familySet->set_id = $set->id;
        $familySet->quantity = $quantity;
        $familySet->status = FamilySetStatus::Sealed;
        $familySet->save();
    }
}

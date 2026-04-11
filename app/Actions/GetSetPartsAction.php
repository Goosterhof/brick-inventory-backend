<?php

declare(strict_types = 1);

namespace App\Actions;

use App\Actions\Sync\StoreSetPartsAction;
use App\Actions\Sync\UpsertSetAction;
use App\Contracts\LegoDataServiceInterface;
use App\Models\Set;

final readonly class GetSetPartsAction
{
    public function __construct(
        private LegoDataServiceInterface $legoDataService,
        private UpsertSetAction $upsertSetAction,
        private StoreSetPartsAction $storeSetPartsAction,
        private Set $set,
    ) {}

    public function execute(string $setNum): Set
    {
        $set = $this->set->newQuery()->where('set_num', $setNum)->first();

        if (!$set instanceof Set || !$set->setParts()->exists()) {
            $setData = $this->legoDataService->fetchSet($setNum);
            $set = $this->upsertSetAction->execute($setData);

            $parts = $this->legoDataService->fetchSetParts($setNum);
            $this->storeSetPartsAction->execute($set, $parts);
        }

        return $set;
    }
}

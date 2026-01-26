<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\LegoDataServiceInterface;
use App\Models\Set;

class GetSetAction
{
    public function __construct(
        private readonly LegoDataServiceInterface $legoDataService,
        private readonly Set $set,
    ) {}

    public function execute(string $setNum): Set
    {
        $set = $this->set->newQuery()->where('set_num', $setNum)->first();

        if ($set instanceof Set) {
            return $set;
        }

        $legoSetData = $this->legoDataService->fetchSet($setNum);

        /** @var Set $newSet */
        $newSet = $this->set->newInstance();
        $newSet->set_num = $legoSetData->setNum;
        $newSet->name = $legoSetData->name;
        $newSet->year = $legoSetData->year;
        $newSet->theme = $legoSetData->themeId !== null ? (string) $legoSetData->themeId : null;
        $newSet->num_parts = $legoSetData->numParts;
        $newSet->image_url = $legoSetData->imageUrl;
        $newSet->save();

        return $newSet;
    }
}

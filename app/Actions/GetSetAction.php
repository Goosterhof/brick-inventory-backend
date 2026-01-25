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

        $setData = $this->legoDataService->fetchSet($setNum);

        /** @var Set $newSet */
        $newSet = $this->set->newInstance();
        $newSet->set_num = $setData->setNum;
        $newSet->name = $setData->name;
        $newSet->year = $setData->year;
        $newSet->theme = $setData->themeId !== null ? (string) $setData->themeId : null;
        $newSet->num_parts = $setData->numParts;
        $newSet->image_url = $setData->imageUrl;
        $newSet->save();

        return $newSet;
    }
}

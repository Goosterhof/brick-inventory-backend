<?php

declare(strict_types=1);

namespace App\Actions\Sync;

use App\Data\Lego\LegoSetData;
use App\Models\Set;

class UpsertSetAction
{
    public function __construct(
        private readonly Set $set,
    ) {}

    public function execute(LegoSetData $legoSetData): Set
    {
        $set = $this->set->newQuery()->where('set_num', $legoSetData->setNum)->first();

        if (!$set instanceof Set) {
            /** @var Set $set */
            $set = $this->set->newInstance();
            $set->set_num = $legoSetData->setNum;
        }

        $set->name = $legoSetData->name;
        $set->year = $legoSetData->year;
        $set->theme = $legoSetData->themeId !== null ? (string) $legoSetData->themeId : null;
        $set->num_parts = $legoSetData->numParts;
        $set->image_url = $legoSetData->imageUrl;
        $set->save();

        return $set;
    }
}

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

    public function execute(LegoSetData $data): Set
    {
        $set = $this->set->newQuery()->where('set_num', $data->setNum)->first();

        if (!$set instanceof Set) {
            /** @var Set $set */
            $set = $this->set->newInstance();
            $set->set_num = $data->setNum;
        }

        $set->name = $data->name;
        $set->year = $data->year;
        $set->theme = $data->themeId !== null ? (string) $data->themeId : null;
        $set->num_parts = $data->numParts;
        $set->image_url = $data->imageUrl;
        $set->save();

        return $set;
    }
}

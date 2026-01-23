<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Set;
use App\Services\RebrickableService;

class GetSetAction
{
    public function __construct(
        private readonly RebrickableService $rebrickableService,
        private readonly Set $set,
    ) {}

    public function execute(string $setNum): Set
    {
        $set = $this->set->newQuery()->where('set_num', $setNum)->first();

        if ($set instanceof Set) {
            return $set;
        }

        $setData = $this->rebrickableService->fetchSet($setNum);

        /** @var Set $newSet */
        $newSet = $this->set->newInstance();
        $newSet->set_num = $setData['set_num'];
        $newSet->name = $setData['name'];
        $newSet->year = $setData['year'];
        $newSet->theme = $setData['theme_id'] !== null ? (string) $setData['theme_id'] : null;
        $newSet->num_parts = $setData['num_parts'];
        $newSet->image_url = $setData['set_img_url'];
        $newSet->save();

        return $newSet;
    }
}

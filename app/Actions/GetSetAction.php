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

        return $this->set->newQuery()->updateOrCreate(
            ['set_num' => $setData['set_num']],
            [
                'name' => $setData['name'],
                'year' => $setData['year'],
                'theme' => $setData['theme_id'] ?? null,
                'num_parts' => $setData['num_parts'],
                'image_url' => $setData['set_img_url'],
            ],
        );
    }
}

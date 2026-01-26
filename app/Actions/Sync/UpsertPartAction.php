<?php

declare(strict_types=1);

namespace App\Actions\Sync;

use App\Data\Lego\LegoPartData;
use App\Models\Part;

class UpsertPartAction
{
    public function __construct(
        private readonly Part $part,
    ) {}

    public function execute(LegoPartData $legoPartData): Part
    {
        $part = $this->part->newQuery()->where('part_num', $legoPartData->partNum)->first();

        if (!$part instanceof Part) {
            /** @var Part $part */
            $part = $this->part->newInstance();
            $part->part_num = $legoPartData->partNum;
        }

        $part->name = $legoPartData->name;
        $part->category = $legoPartData->categoryId !== null ? (string) $legoPartData->categoryId : null;
        $part->image_url = $legoPartData->imageUrl;
        $part->save();

        return $part;
    }
}

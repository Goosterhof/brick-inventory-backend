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

    public function execute(LegoPartData $data): Part
    {
        $part = $this->part->newQuery()->where('part_num', $data->partNum)->first();

        if (!$part instanceof Part) {
            /** @var Part $part */
            $part = $this->part->newInstance();
            $part->part_num = $data->partNum;
        }

        $part->name = $data->name;
        $part->category = $data->categoryId !== null ? (string) $data->categoryId : null;
        $part->image_url = $data->imageUrl;
        $part->save();

        return $part;
    }
}

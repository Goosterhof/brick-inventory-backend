<?php

declare(strict_types=1);

namespace App\Actions\Sync;

use App\Models\Part;

class UpsertPartAction
{
    public function __construct(
        private readonly Part $part,
    ) {}

    /**
     * @param  array{part_num: string, name: string, part_cat_id: int|null, part_img_url: string|null}  $data
     */
    public function execute(array $data): Part
    {
        $part = $this->part->newQuery()->where('part_num', $data['part_num'])->first();

        if (!$part instanceof Part) {
            /** @var Part $part */
            $part = $this->part->newInstance();
            $part->part_num = $data['part_num'];
        }

        $part->name = $data['name'];
        $part->category = $data['part_cat_id'] !== null ? (string) $data['part_cat_id'] : null;
        $part->image_url = $data['part_img_url'];
        $part->save();

        return $part;
    }
}

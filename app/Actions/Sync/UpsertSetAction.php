<?php

declare(strict_types=1);

namespace App\Actions\Sync;

use App\Models\Set;

class UpsertSetAction
{
    public function __construct(
        private readonly Set $set,
    ) {}

    /**
     * @param  array{set_num: string, name: string, year: int, theme_id: int|null, num_parts: int, set_img_url: string|null}  $data
     */
    public function execute(array $data): Set
    {
        $set = $this->set->newQuery()->where('set_num', $data['set_num'])->first();

        if (!$set instanceof Set) {
            /** @var Set $set */
            $set = $this->set->newInstance();
            $set->set_num = $data['set_num'];
        }

        $set->name = $data['name'];
        $set->year = $data['year'];
        $set->theme = $data['theme_id'] !== null ? (string) $data['theme_id'] : null;
        $set->num_parts = $data['num_parts'];
        $set->image_url = $data['set_img_url'];
        $set->save();

        return $set;
    }
}

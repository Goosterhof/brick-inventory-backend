<?php

declare(strict_types=1);

namespace App\Actions\Sync;

use App\Models\Set;
use App\Models\SetPart;

class StoreSetPartsAction
{
    public function __construct(
        private readonly UpsertColorAction $upsertColorAction,
        private readonly UpsertPartAction $upsertPartAction,
        private readonly SetPart $setPart,
    ) {}

    /**
     * @param  list<array{part: array{part_num: string, name: string, part_cat_id: int|null, part_img_url: string|null}, color: array{id: int, name: string, rgb: string, is_trans: bool}, quantity: int, is_spare: bool, element_id: string|null}>  $partsData
     */
    public function execute(Set $set, array $partsData): void
    {
        foreach ($partsData as $partData) {
            $color = $this->upsertColorAction->execute($partData['color']);
            $part = $this->upsertPartAction->execute($partData['part']);

            $setPart = $this->setPart->newQuery()
                ->where('set_id', $set->id)
                ->where('part_id', $part->id)
                ->where('color_id', $color->id)
                ->where('is_spare', $partData['is_spare'])
                ->first();

            if (!$setPart instanceof SetPart) {
                /** @var SetPart $setPart */
                $setPart = $this->setPart->newInstance();
                $setPart->set_id = $set->id;
                $setPart->part_id = $part->id;
                $setPart->color_id = $color->id;
                $setPart->is_spare = $partData['is_spare'];
            }

            $setPart->quantity = $partData['quantity'];
            $setPart->element_id = $partData['element_id'];
            $setPart->save();
        }
    }
}

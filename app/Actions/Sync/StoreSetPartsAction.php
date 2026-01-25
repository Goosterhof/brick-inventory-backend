<?php

declare(strict_types=1);

namespace App\Actions\Sync;

use App\Data\Lego\LegoSetPartData;
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
     * @param  list<LegoSetPartData>  $partsData
     */
    public function execute(Set $set, array $partsData): void
    {
        foreach ($partsData as $partData) {
            $color = $this->upsertColorAction->execute($partData->color);
            $part = $this->upsertPartAction->execute($partData->part);

            $setPart = $this->setPart->newQuery()
                ->where('set_id', $set->id)
                ->where('part_id', $part->id)
                ->where('color_id', $color->id)
                ->where('is_spare', $partData->isSpare)
                ->first();

            if (!$setPart instanceof SetPart) {
                /** @var SetPart $setPart */
                $setPart = $this->setPart->newInstance();
                $setPart->set_id = $set->id;
                $setPart->part_id = $part->id;
                $setPart->color_id = $color->id;
                $setPart->is_spare = $partData->isSpare;
            }

            $setPart->quantity = $partData->quantity;
            $setPart->element_id = $partData->elementId;
            $setPart->save();
        }
    }
}

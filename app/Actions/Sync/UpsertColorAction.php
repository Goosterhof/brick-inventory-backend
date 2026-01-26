<?php

declare(strict_types=1);

namespace App\Actions\Sync;

use App\Data\Lego\LegoColorData;
use App\Models\Color;

class UpsertColorAction
{
    public function __construct(
        private readonly Color $color,
    ) {}

    public function execute(LegoColorData $legoColorData): Color
    {
        $color = $this->color->newQuery()->where('rebrickable_id', $legoColorData->id)->first();

        if (!$color instanceof Color) {
            /** @var Color $color */
            $color = $this->color->newInstance();
            $color->rebrickable_id = $legoColorData->id;
        }

        $color->name = $legoColorData->name;
        $color->rgb = $legoColorData->rgb;
        $color->is_transparent = $legoColorData->isTransparent;
        $color->save();

        return $color;
    }
}

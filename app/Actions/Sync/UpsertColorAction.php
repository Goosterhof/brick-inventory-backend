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

    public function execute(LegoColorData $data): Color
    {
        $color = $this->color->newQuery()->where('rebrickable_id', $data->id)->first();

        if (!$color instanceof Color) {
            /** @var Color $color */
            $color = $this->color->newInstance();
            $color->rebrickable_id = $data->id;
        }

        $color->name = $data->name;
        $color->rgb = $data->rgb;
        $color->is_transparent = $data->isTransparent;
        $color->save();

        return $color;
    }
}

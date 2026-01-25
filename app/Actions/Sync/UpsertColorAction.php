<?php

declare(strict_types=1);

namespace App\Actions\Sync;

use App\Models\Color;

class UpsertColorAction
{
    public function __construct(
        private readonly Color $color,
    ) {}

    /**
     * @param  array{id: int, name: string, rgb: string, is_trans: bool}  $data
     */
    public function execute(array $data): Color
    {
        $color = $this->color->newQuery()->where('rebrickable_id', $data['id'])->first();

        if (!$color instanceof Color) {
            /** @var Color $color */
            $color = $this->color->newInstance();
            $color->rebrickable_id = $data['id'];
        }

        $color->name = $data['name'];
        $color->rgb = $data['rgb'];
        $color->is_transparent = $data['is_trans'];
        $color->save();

        return $color;
    }
}

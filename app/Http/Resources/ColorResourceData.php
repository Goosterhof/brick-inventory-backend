<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Color;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ResourceData<Color>
 */
final readonly class ColorResourceData extends ResourceData
{
    public function __construct(
        public int $id,
        public int $rebrickableId,
        public string $name,
        public string $rgb,
        public bool $isTransparent,
    ) {}

    public static function from(Model $model): static
    {
        /** @var Color $model */
        return new self(
            id: $model->id,
            rebrickableId: $model->rebrickable_id,
            name: $model->name,
            rgb: $model->rgb,
            isTransparent: $model->is_transparent,
        );
    }
}

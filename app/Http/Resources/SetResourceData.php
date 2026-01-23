<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Set;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ResourceData<Set>
 */
final readonly class SetResourceData extends ResourceData
{
    public function __construct(
        public int $id,
        public string $setNum,
        public string $name,
        public ?int $year,
        public ?string $theme,
        public int $numParts,
        public ?string $imageUrl,
    ) {}

    public static function from(Model $model): static
    {
        /** @var Set $model */
        return new self(
            id: $model->id,
            setNum: $model->set_num,
            name: $model->name,
            year: $model->year,
            theme: $model->theme,
            numParts: $model->num_parts,
            imageUrl: $model->image_url,
        );
    }
}

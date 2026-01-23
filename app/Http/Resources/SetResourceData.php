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
        public string $set_num,
        public string $name,
        public ?int $year,
        public ?string $theme,
        public int $num_parts,
        public ?string $image_url,
    ) {}

    public static function from(Model $model): static
    {
        return new self(
            id: $model->id,
            set_num: $model->set_num,
            name: $model->name,
            year: $model->year,
            theme: $model->theme,
            num_parts: $model->num_parts,
            image_url: $model->image_url,
        );
    }
}

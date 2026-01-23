<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Part;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ResourceData<Part>
 */
final readonly class PartResourceData extends ResourceData
{
    public function __construct(
        public int $id,
        public string $partNum,
        public string $name,
        public ?string $category,
        public ?string $imageUrl,
    ) {}

    public static function from(Model $model): static
    {
        /** @var Part $model */
        return new self(
            id: $model->id,
            partNum: $model->part_num,
            name: $model->name,
            category: $model->category,
            imageUrl: $model->image_url,
        );
    }
}

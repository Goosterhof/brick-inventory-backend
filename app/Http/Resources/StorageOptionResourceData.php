<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StorageOption;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ResourceData<StorageOption>
 */
final readonly class StorageOptionResourceData extends ResourceData
{
    /**
     * @param  array<int, StorageOptionResourceData>|MissingValue  $children
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?int $parentId,
        public ?int $row,
        public ?int $column,
        public array|MissingValue $children,
        public ?Carbon $createdAt,
        public ?Carbon $updatedAt,
    ) {}

    public static function from(Model $model): static
    {
        /** @var StorageOption $model */
        return new self(
            id: $model->id,
            name: $model->name,
            description: $model->description,
            parentId: $model->parent_id,
            row: $model->row,
            column: $model->column,
            children: self::whenLoaded($model, 'children', fn (): array => array_map(
                self::from(...),
                $model->children->all(),
            )),
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}

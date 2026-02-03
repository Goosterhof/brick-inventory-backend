<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StorageOption;
use Carbon\Carbon;

/**
 * @extends ResourceData<StorageOption>
 */
final readonly class StorageOptionResourceData extends ResourceData
{
    /**
     * @param array<int, StorageOptionResourceData> $children
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?int $parent_id,
        public ?int $row,
        public ?int $column,
        public array $children,
        public ?Carbon $created_at,
        public ?Carbon $updated_at,
    ) {}

    /**
     * @param StorageOption $model
     */
    public static function from($model): static
    {
        $model->loadMissing(self::requiredRelations());

        return new self(
            id: $model->id,
            name: $model->name,
            description: $model->description,
            parent_id: $model->parent_id,
            row: $model->row,
            column: $model->column,
            children: array_map(
                self::from(...),
                $model->children->all(),
            ),
            created_at: $model->created_at,
            updated_at: $model->updated_at,
        );
    }

    protected static function requiredRelations(): array
    {
        return ['children'];
    }
}

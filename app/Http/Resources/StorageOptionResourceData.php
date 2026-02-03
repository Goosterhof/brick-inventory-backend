<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StorageOption;

/**
 * @extends ResourceData<StorageOption>
 */
final readonly class StorageOptionResourceData extends ResourceData
{
    /**
     * @param array<int, int> $child_ids
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?int $parent_id,
        public ?int $row,
        public ?int $column,
        public array $child_ids,
    ) {}

    /**
     * @param StorageOption $model
     */
    public static function from($model): static
    {
        $model->loadMissing(self::requiredRelations());
        self::validateRelationsLoaded($model);

        /** @var array<int, int> $childIds */
        $childIds = $model->children->pluck('id')->all();

        return new self(
            id: $model->id,
            name: $model->name,
            description: $model->description,
            parent_id: $model->parent_id,
            row: $model->row,
            column: $model->column,
            child_ids: $childIds,
        );
    }

    protected static function requiredRelations(): array
    {
        return ['children'];
    }
}

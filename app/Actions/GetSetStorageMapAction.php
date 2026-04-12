<?php

declare(strict_types = 1);

namespace App\Actions;

use App\Models\Family;
use App\Models\Set;
use App\Models\StorageOptionPart;

final readonly class GetSetStorageMapAction
{
    public function __construct(
        private StorageOptionPart $storageOptionPart,
    ) {}

    /**
     * Get storage locations for each part in a set, scoped to a family.
     *
     * @return array<int, array{part_id: int, color_id: int|null, storage_option_id: int, storage_option_name: string, quantity: int}>
     */
    public function execute(Set $set, Family $family): array
    {
        $partIds = $set->setParts()->pluck('part_id')->unique()->toArray();

        if ($partIds === []) {
            return [];
        }

        return $this->storageOptionPart->newQuery()
            ->join('storage_options', 'storage_option_parts.storage_option_id', '=', 'storage_options.id')
            ->where('storage_options.family_id', $family->id)
            ->whereIn('storage_option_parts.part_id', $partIds)
            ->select([
                'storage_option_parts.part_id',
                'storage_option_parts.color_id',
                'storage_option_parts.storage_option_id',
                'storage_options.name as storage_option_name',
                'storage_option_parts.quantity',
            ])
            ->get()
            ->map(function(StorageOptionPart $storageOptionPart): array {
                /** @var string $name */
                $name = $storageOptionPart->getAttribute('storage_option_name');

                return [
                    'part_id' => $storageOptionPart->part_id,
                    'color_id' => $storageOptionPart->color_id,
                    'storage_option_id' => $storageOptionPart->storage_option_id,
                    'storage_option_name' => $name,
                    'quantity' => $storageOptionPart->quantity,
                ];
            })
            ->values()
            ->all();
    }
}

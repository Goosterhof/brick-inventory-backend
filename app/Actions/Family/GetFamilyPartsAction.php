<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Models\Family;
use App\Models\StorageOptionPart;

final readonly class GetFamilyPartsAction
{
    public function __construct(
        private StorageOptionPart $storageOptionPart,
    ) {}

    /**
     * Get all parts stored across all storage locations for a family.
     *
     * @return array<int, array{part_id: int, part_num: string, part_name: string, part_image_url: string|null, color_id: int|null, color_name: string|null, color_rgb: string|null, storage_option_id: int, storage_option_name: string, quantity: int}>
     */
    public function execute(Family $family): array
    {
        return $this->storageOptionPart->newQuery()
            ->join('storage_options', 'storage_option_parts.storage_option_id', '=', 'storage_options.id')
            ->join('parts', 'storage_option_parts.part_id', '=', 'parts.id')
            ->leftJoin('colors', 'storage_option_parts.color_id', '=', 'colors.id')
            ->where('storage_options.family_id', $family->id)
            ->select([
                'storage_option_parts.part_id',
                'parts.part_num',
                'parts.name as part_name',
                'parts.image_url as part_image_url',
                'storage_option_parts.color_id',
                'colors.name as color_name',
                'colors.rgb as color_rgb',
                'storage_option_parts.storage_option_id',
                'storage_options.name as storage_option_name',
                'storage_option_parts.quantity',
            ])
            ->orderBy('parts.name')
            ->get()
            ->map(function (StorageOptionPart $storageOptionPart): array {
                /** @var string $partNum */
                $partNum = $storageOptionPart->getAttribute('part_num');
                /** @var string $partName */
                $partName = $storageOptionPart->getAttribute('part_name');
                /** @var string $storageName */
                $storageName = $storageOptionPart->getAttribute('storage_option_name');
                /** @var string|null $partImageUrl */
                $partImageUrl = $storageOptionPart->getAttribute('part_image_url');
                /** @var string|null $colorName */
                $colorName = $storageOptionPart->getAttribute('color_name');
                /** @var string|null $colorRgb */
                $colorRgb = $storageOptionPart->getAttribute('color_rgb');

                return [
                    'part_id' => $storageOptionPart->part_id,
                    'part_num' => $partNum,
                    'part_name' => $partName,
                    'part_image_url' => $partImageUrl,
                    'color_id' => $storageOptionPart->color_id,
                    'color_name' => $colorName,
                    'color_rgb' => $colorRgb,
                    'storage_option_id' => $storageOptionPart->storage_option_id,
                    'storage_option_name' => $storageName,
                    'quantity' => $storageOptionPart->quantity,
                ];
            })
            ->values()
            ->all();
    }
}

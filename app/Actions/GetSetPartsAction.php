<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataTransferObjects\SetPartData;
use App\DataTransferObjects\SetPartsResultData;
use App\Services\RebrickableService;

class GetSetPartsAction
{
    public function __construct(
        private readonly RebrickableService $rebrickableService,
    ) {}

    public function execute(string $setNum): SetPartsResultData
    {
        return $this->rebrickableService->getSetParts($setNum);
    }

    /**
     * @return array{set: array{set_num: string, name: string, year: int|null, theme: int|string|null, num_parts: int, image_url: string|null}, parts: list<array{part_num: string, name: string, category: string|null, image_url: string|null, color: array{id: int, name: string, rgb: string, is_transparent: bool}, quantity: int, is_spare: bool, element_id: string|null}>}
     */
    public function toArray(SetPartsResultData $result): array
    {
        return [
            'set' => [
                'set_num' => $result->set->setNum,
                'name' => $result->set->name,
                'year' => $result->set->year,
                'theme' => $result->set->theme,
                'num_parts' => $result->set->numParts,
                'image_url' => $result->set->imageUrl,
            ],
            'parts' => array_values(array_map(fn (SetPartData $part): array => [
                'part_num' => $part->partNum,
                'name' => $part->name,
                'category' => $part->category,
                'image_url' => $part->imageUrl,
                'color' => [
                    'id' => $part->color->id,
                    'name' => $part->color->name,
                    'rgb' => $part->color->rgb,
                    'is_transparent' => $part->color->isTransparent,
                ],
                'quantity' => $part->quantity,
                'is_spare' => $part->isSpare,
                'element_id' => $part->elementId,
            ], $result->parts)),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DataTransferObjects\SetPartData;
use App\DataTransferObjects\SetPartsResultData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property SetPartsResultData $resource
 */
class SetPartsResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap;

    /**
     * @return array{set: array{set_num: string, name: string, year: int|null, theme: int|string|null, num_parts: int, image_url: string|null}, parts: list<array{part_num: string, name: string, category: string|null, image_url: string|null, color: array{id: int, name: string, rgb: string, is_transparent: bool}, quantity: int, is_spare: bool, element_id: string|null}>}
     */
    public function toArray(Request $request): array
    {
        return [
            'set' => [
                'set_num' => $this->resource->set->setNum,
                'name' => $this->resource->set->name,
                'year' => $this->resource->set->year,
                'theme' => $this->resource->set->theme,
                'num_parts' => $this->resource->set->numParts,
                'image_url' => $this->resource->set->imageUrl,
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
            ], $this->resource->parts)),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StorageOptionPart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property StorageOptionPart $resource
 */
class StorageOptionPartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'storage_option_id' => $this->resource->storage_option_id,
            'part_id' => $this->resource->part_id,
            'color_id' => $this->resource->color_id,
            'quantity' => $this->resource->quantity,
            'part' => new PartResource($this->whenLoaded('part')),
            'color' => new ColorResource($this->whenLoaded('color')),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}

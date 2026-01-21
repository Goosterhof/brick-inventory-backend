<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Part;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Part $resource
 */
class PartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'part_num' => $this->resource->part_num,
            'name' => $this->resource->name,
            'category' => $this->resource->category,
            'image_url' => $this->resource->image_url,
        ];
    }
}

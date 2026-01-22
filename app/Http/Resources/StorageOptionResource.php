<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StorageOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property StorageOption $resource
 */
class StorageOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'parent_id' => $this->resource->parent_id,
            'row' => $this->resource->row,
            'column' => $this->resource->column,
            'children' => self::collection($this->whenLoaded('children')),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}

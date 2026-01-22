<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Color $resource
 */
class ColorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'rebrickable_id' => $this->resource->rebrickable_id,
            'name' => $this->resource->name,
            'rgb' => $this->resource->rgb,
            'is_transparent' => $this->resource->is_transparent,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\FamilySet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property FamilySet $resource
 */
class FamilySetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'quantity' => $this->resource->quantity,
            'status' => $this->resource->status->value,
            'purchase_date' => $this->resource->purchase_date?->format('Y-m-d'),
            'notes' => $this->resource->notes,
            'set' => [
                'id' => $this->resource->set->id,
                'set_num' => $this->resource->set->set_num,
                'name' => $this->resource->set->name,
                'year' => $this->resource->set->year,
                'theme' => $this->resource->set->theme,
                'num_parts' => $this->resource->set->num_parts,
                'image_url' => $this->resource->set->image_url,
            ],
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}

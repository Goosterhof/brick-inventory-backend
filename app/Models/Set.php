<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\SetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property positive-int $id
 * @property string $set_num
 * @property string $name
 * @property int|null $year
 * @property string|null $theme
 * @property int $num_parts
 * @property string|null $image_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Set extends Model
{
    /** @use HasFactory<SetFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<Part, $this>
     */
    public function parts(): BelongsToMany
    {
        return $this->belongsToMany(Part::class, 'set_parts')
            ->withPivot(['color_id', 'quantity', 'is_spare', 'element_id'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<SetPart, $this>
     */
    public function setParts(): HasMany
    {
        return $this->hasMany(SetPart::class);
    }

    /**
     * @return HasMany<FamilySet, $this>
     */
    public function familySets(): HasMany
    {
        return $this->hasMany(FamilySet::class);
    }
}

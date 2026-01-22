<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Set extends Model
{
    /** @use HasFactory<SetFactory> */
    use HasFactory;

    protected $guarded = [];

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

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Set extends Model
{
    protected $fillable = [
        'set_num',
        'name',
        'year',
        'theme',
        'num_parts',
        'image_url',
    ];

    public function parts(): BelongsToMany
    {
        return $this->belongsToMany(Part::class, 'set_parts')
            ->withPivot(['color_id', 'quantity', 'is_spare', 'element_id'])
            ->withTimestamps();
    }

    public function setParts(): HasMany
    {
        return $this->hasMany(SetPart::class);
    }
}

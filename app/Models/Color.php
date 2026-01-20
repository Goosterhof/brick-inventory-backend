<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Color extends Model
{
    protected $fillable = [
        'rebrickable_id',
        'name',
        'rgb',
        'is_transparent',
    ];

    public function setParts(): HasMany
    {
        return $this->hasMany(SetPart::class);
    }

    protected function casts(): array
    {
        return [
            'is_transparent' => 'boolean',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Color extends Model
{
    protected $guarded = [];

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

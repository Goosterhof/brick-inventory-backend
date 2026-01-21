<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ColorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Color extends Model
{
    /** @use HasFactory<ColorFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return HasMany<SetPart, $this>
     */
    public function setParts(): HasMany
    {
        return $this->hasMany(SetPart::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_transparent' => 'boolean',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SetPart extends Model
{
    protected $fillable = [
        'set_id',
        'part_id',
        'color_id',
        'quantity',
        'is_spare',
        'element_id',
    ];

    public function set(): BelongsTo
    {
        return $this->belongsTo(Set::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    protected function casts(): array
    {
        return [
            'is_spare' => 'boolean',
        ];
    }
}

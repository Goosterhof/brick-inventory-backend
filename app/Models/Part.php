<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends Model
{
    /** @use HasFactory<PartFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'part_num',
        'name',
        'category',
        'image_url',
    ];

    /**
     * @return BelongsToMany<Set, $this>
     */
    public function sets(): BelongsToMany
    {
        return $this->belongsToMany(Set::class, 'set_parts')
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
     * @return HasMany<StorageOptionPart, $this>
     */
    public function storageOptionParts(): HasMany
    {
        return $this->hasMany(StorageOptionPart::class);
    }
}

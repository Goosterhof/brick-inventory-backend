<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StorageOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property positive-int $id
 * @property int $family_id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $description
 * @property int|null $row
 * @property int|null $column
 */
class StorageOption extends Model
{
    /** @use HasFactory<StorageOptionFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return BelongsTo<StorageOption, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<StorageOption, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<StorageOptionPart, $this>
     */
    public function storageOptionParts(): HasMany
    {
        return $this->hasMany(StorageOptionPart::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StorageOptionPartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property positive-int $id
 * @property int $storage_option_id
 * @property int $part_id
 * @property int|null $color_id
 * @property int $quantity
 */
class StorageOptionPart extends Model
{
    /** @use HasFactory<StorageOptionPartFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'storage_option_id',
        'part_id',
        'color_id',
        'quantity',
    ];

    /**
     * @return BelongsTo<StorageOption, $this>
     */
    public function storageOption(): BelongsTo
    {
        return $this->belongsTo(StorageOption::class);
    }

    /**
     * @return BelongsTo<Part, $this>
     */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    /**
     * @return BelongsTo<Color, $this>
     */
    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }
}

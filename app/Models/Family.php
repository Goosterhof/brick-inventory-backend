<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    use HasFactory;

    /**
     * Get the users belonging to this family.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

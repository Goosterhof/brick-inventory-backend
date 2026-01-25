<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\Models\StorageOption;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListStorageOptionsAction
{
    /**
     * @return Collection<int, StorageOption>
     */
    public function execute(User $user): Collection
    {
        return StorageOption::where('family_id', $user->family_id)
            ->whereNull('parent_id')
            ->with('children')
            ->get();
    }
}

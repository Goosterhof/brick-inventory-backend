<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\Models\FamilySet;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListFamilySetsAction
{
    /**
     * @return Collection<int, FamilySet>
     */
    public function execute(User $user): Collection
    {
        return FamilySet::where('family_id', $user->family_id)
            ->with('set')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

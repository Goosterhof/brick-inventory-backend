<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\Models\FamilySet;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GetFamilySetsAction
{
    public function __construct(
        private readonly FamilySet $familySet,
    ) {}

    /**
     * @return Collection<int, FamilySet>
     */
    public function execute(User $user): Collection
    {
        return $this->familySet->newQuery()
            ->where('family_id', $user->family_id)
            ->latest()
            ->get();
    }
}

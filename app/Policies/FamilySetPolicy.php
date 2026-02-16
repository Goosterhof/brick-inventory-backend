<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FamilySet;
use App\Models\User;

final readonly class FamilySetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FamilySet $familySet): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FamilySet $familySet): bool
    {
        return true;
    }

    public function delete(User $user, FamilySet $familySet): bool
    {
        return true;
    }

    public function importFromRebrickable(User $user): bool
    {
        return $user->family->head_id === $user->id;
    }
}

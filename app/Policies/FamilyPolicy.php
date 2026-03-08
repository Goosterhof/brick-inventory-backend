<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final readonly class FamilyPolicy
{
    public function setRebrickableToken(User $user): bool
    {
        return $user->family->head_id === $user->id;
    }
}

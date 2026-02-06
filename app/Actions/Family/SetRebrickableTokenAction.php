<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Contracts\Family\SetRebrickableTokenInterface;
use App\Exceptions\NotFamilyHeadException;
use App\Models\Family;
use App\Models\User;

final readonly class SetRebrickableTokenAction
{
    public function execute(Family $family, SetRebrickableTokenInterface $setRebrickableToken, User $user): Family
    {
        if ($family->head_id !== $user->id) {
            throw NotFamilyHeadException::forUser($user->id);
        }

        $family->rebrickable_user_token = $setRebrickableToken->rebrickableUserToken;
        $family->save();

        return $family;
    }
}

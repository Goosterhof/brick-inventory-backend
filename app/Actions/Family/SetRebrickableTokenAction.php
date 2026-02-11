<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\DataTransferObjects\Family\SetRebrickableTokenData;
use App\Exceptions\NotFamilyHeadException;
use App\Models\Family;
use App\Models\User;

final readonly class SetRebrickableTokenAction
{
    public function execute(Family $family, SetRebrickableTokenData $setRebrickableTokenData, User $user): Family
    {
        if ($family->head_id !== $user->id) {
            throw NotFamilyHeadException::forUser($user->id);
        }

        $family->rebrickable_user_token = $setRebrickableTokenData->rebrickableUserToken;
        $family->save();

        return $family;
    }
}

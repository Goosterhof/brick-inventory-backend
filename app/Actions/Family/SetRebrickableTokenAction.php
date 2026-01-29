<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Contracts\Family\SetRebrickableTokenInterface;
use App\Models\Family;

class SetRebrickableTokenAction
{
    public function execute(Family $family, SetRebrickableTokenInterface $setRebrickableToken): Family
    {
        $family->rebrickable_user_token = $setRebrickableToken->rebrickableUserToken;
        $family->save();

        return $family;
    }
}

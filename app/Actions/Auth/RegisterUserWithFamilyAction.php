<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObjects\RegisterUserData;
use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterUserWithFamilyAction
{
    public function execute(RegisterUserData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $family = new Family;
            $family->name = $data->familyName;
            $family->save();

            /** @var positive-int $familyId */
            $familyId = $family->id;

            $user = new User;
            $user->name = $data->name;
            $user->email = $data->email;
            $user->password = $data->password;
            $user->family_id = $familyId;
            $user->save();

            return $user;
        });
    }
}

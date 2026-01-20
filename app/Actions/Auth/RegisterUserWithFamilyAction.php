<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterUserWithFamilyAction
{
    /**
     * @param  array{family_name: string, name: string, email: string, password: string}  $data
     */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $family = new Family;
            $family->name = $data['family_name'];
            $family->save();

            /** @var positive-int $familyId */
            $familyId = $family->id;

            $user = new User;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->family_id = $familyId;
            $user->save();

            return $user;
        });
    }
}

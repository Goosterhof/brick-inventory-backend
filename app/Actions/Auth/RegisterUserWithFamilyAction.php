<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObjects\RegisterUserData;
use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterUserWithFamilyAction
{
    public function __construct(
        private readonly User $user,
        private readonly Family $family,
    ) {}

    public function execute(RegisterUserData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $family = $this->family->newInstance(['name' => $data->familyName]);
            $family->save();

            $user = $this->user->newInstance([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
            ]);

            $family->users()->save($user);

            return $user;
        });
    }
}

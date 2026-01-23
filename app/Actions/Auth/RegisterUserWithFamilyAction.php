<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\Auth\RegisterUserInterface;
use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterUserWithFamilyAction
{
    public function __construct(
        private readonly User $user,
        private readonly Family $family,
    ) {}

    public function execute(RegisterUserInterface $data): User
    {
        return DB::transaction(function () use ($data): User {
            $family = $this->family->newInstance();
            $family->name = $data->familyName;
            $family->save();

            $user = $this->user->newInstance();
            $user->name = $data->name;
            $user->email = $data->email;
            $user->password = $data->password;

            $family->users()->save($user);

            return $user;
        });
    }
}

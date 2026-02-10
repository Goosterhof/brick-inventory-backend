<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\Auth\RegisterUserInterface;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;

final readonly class CreateUserWithFamilyAction
{
    public function __construct(
        private User $user,
        private Family $family,
        private ConnectionInterface $connection,
    ) {}

    public function execute(RegisterUserInterface $registerUser): User
    {
        return $this->connection->transaction(function () use ($registerUser): User {
            $family = $this->family->newInstance();
            $family->name = $registerUser->familyName;
            $family->save();

            $user = $this->user->newInstance();
            $user->name = $registerUser->name;
            $user->email = $registerUser->email;
            $user->password = $registerUser->password;

            $family->users()->save($user);

            /** @var positive-int $userId */
            $userId = $user->id;
            $family->head_id = $userId;
            $family->save();

            return $user;
        });
    }
}

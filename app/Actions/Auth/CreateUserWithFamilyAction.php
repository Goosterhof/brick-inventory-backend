<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObjects\Auth\RegisterUserData;
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

    public function execute(RegisterUserData $registerUserData): User
    {
        return $this->connection->transaction(function () use ($registerUserData): User {
            $family = $this->family->newInstance();
            $family->name = $registerUserData->familyName;
            $family->save();

            $user = $this->user->newInstance();
            $user->name = $registerUserData->name;
            $user->email = $registerUserData->email;
            $user->password = $registerUserData->password;

            $family->users()->save($user);

            /** @var positive-int $userId */
            $userId = $user->id;
            $family->head_id = $userId;
            $family->save();

            return $user;
        });
    }
}

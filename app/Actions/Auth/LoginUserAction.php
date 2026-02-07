<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\Auth\LoginUserInterface;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Validation\ValidationException;

final readonly class LoginUserAction
{
    public function __construct(
        private User $user,
        private Hasher $hasher,
    ) {}

    public function execute(LoginUserInterface $loginUser): User
    {
        $user = $this->user->newQuery()->where('email', $loginUser->email)->first();

        if ($user === null || !$this->hasher->check($loginUser->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $user;
    }
}

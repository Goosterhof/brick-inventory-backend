<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\Auth\LoginUserInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final readonly class LoginUserAction
{
    public function execute(LoginUserInterface $loginUser): User
    {
        $user = User::query()->where('email', $loginUser->email)->first();

        if (!$user || !Hash::check($loginUser->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $user;
    }
}

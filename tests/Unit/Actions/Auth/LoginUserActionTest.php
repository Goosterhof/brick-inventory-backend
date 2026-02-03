<?php

declare(strict_types=1);

use App\Actions\Auth\LoginUserAction;
use App\Contracts\Auth\LoginUserInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

describe('LoginUserAction', function (): void {
    it('should return user when credentials are valid', function (): void {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $loginData = new class implements LoginUserInterface
        {
            public string $email { get => 'john@example.com'; }

            public string $password { get => 'password123'; }
        };

        $action = new LoginUserAction;
        $result = $action->execute($loginData);

        expect($result->id)->toBe($user->id);
        expect($result->email)->toBe('john@example.com');
    });

    it('should throw validation exception when password is incorrect', function (): void {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $loginData = new class implements LoginUserInterface
        {
            public string $email { get => 'john@example.com'; }

            public string $password { get => 'wrongpassword'; }
        };

        $action = new LoginUserAction;
        $action->execute($loginData);
    })->throws(ValidationException::class);

    it('should throw validation exception when user does not exist', function (): void {
        $loginData = new class implements LoginUserInterface
        {
            public string $email { get => 'nonexistent@example.com'; }

            public string $password { get => 'password123'; }
        };

        $action = new LoginUserAction;
        $action->execute($loginData);
    })->throws(ValidationException::class);
});

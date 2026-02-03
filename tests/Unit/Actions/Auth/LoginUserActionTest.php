<?php

declare(strict_types=1);

use App\Actions\Auth\LoginUserAction;
use App\Contracts\Auth\LoginUserInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

describe('LoginUserAction', function (): void {
    it('should return user when credentials are valid', function (): void {
        // arrange
        $userInstance = Mockery::mock(User::class)->makePartial();
        $userInstance->id = 1;
        $userInstance->email = 'john@example.com';
        $userInstance->password = 'hashed_password';

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')
            ->with('email', 'john@example.com')
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('first')
            ->once()
            ->andReturn($userInstance);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newQuery')
            ->once()
            ->andReturn($builder);

        Hash::shouldReceive('check')
            ->with('password123', 'hashed_password')
            ->once()
            ->andReturn(true);

        $loginData = new class implements LoginUserInterface
        {
            public string $email { get => 'john@example.com'; }

            public string $password { get => 'password123'; }
        };

        $action = new LoginUserAction($user);

        // act
        $result = $action->execute($loginData);

        // assert
        expect($result->id)->toBe(1);
        expect($result->email)->toBe('john@example.com');
    });

    it('should throw validation exception when password is incorrect', function (): void {
        // arrange
        $userInstance = Mockery::mock(User::class)->makePartial();
        $userInstance->password = 'hashed_password';

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')
            ->with('email', 'john@example.com')
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('first')
            ->once()
            ->andReturn($userInstance);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newQuery')
            ->once()
            ->andReturn($builder);

        Hash::shouldReceive('check')
            ->with('wrongpassword', 'hashed_password')
            ->once()
            ->andReturn(false);

        $loginData = new class implements LoginUserInterface
        {
            public string $email { get => 'john@example.com'; }

            public string $password { get => 'wrongpassword'; }
        };

        $action = new LoginUserAction($user);

        // act & assert
        $action->execute($loginData);
    })->throws(ValidationException::class);

    it('should throw validation exception when user does not exist', function (): void {
        // arrange
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')
            ->with('email', 'nonexistent@example.com')
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('first')
            ->once()
            ->andReturn(null);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newQuery')
            ->once()
            ->andReturn($builder);

        $loginData = new class implements LoginUserInterface
        {
            public string $email { get => 'nonexistent@example.com'; }

            public string $password { get => 'password123'; }
        };

        $action = new LoginUserAction($user);

        // act & assert
        $action->execute($loginData);
    })->throws(ValidationException::class);
});

<?php

declare(strict_types=1);

use App\Actions\Auth\CreateUserWithFamilyAction;
use App\Contracts\Auth\RegisterUserInterface;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

describe('CreateUserWithFamilyAction', function (): void {
    it('should create a family with the provided name', function (): void {
        // arrange
        $familySavedValues = [];
        $familyInstance = Mockery::mock(Family::class);
        $familyInstance->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$familySavedValues): void {
            $familySavedValues[$key] = $value;
        });
        $familyInstance->allows('getAttribute')->andReturnUsing(function ($key) use (&$familySavedValues): mixed {
            return $familySavedValues[$key] ?? null;
        });
        $familyInstance->shouldReceive('save')->twice();
        $familyInstance->shouldReceive('users->save')->once();

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('newInstance')
            ->withNoArgs()
            ->once()
            ->andReturn($familyInstance);

        $userSavedValues = [];
        $userInstance = Mockery::mock(User::class);
        $userInstance->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$userSavedValues): void {
            $userSavedValues[$key] = $value;
        });
        $userInstance->allows('getAttribute')->andReturnUsing(function ($key) use (&$userSavedValues): mixed {
            return $userSavedValues[$key] ?? null;
        });

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newInstance')
            ->withNoArgs()
            ->once()
            ->andReturn($userInstance);

        $action = new CreateUserWithFamilyAction($user, $family);
        $data = new class implements RegisterUserInterface
        {
            public string $familyName = 'Test Family';

            public string $name = 'Test User';

            public string $email = 'test@example.com';

            public string $password = 'password123';
        };

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($userInstance)
            ->and($familySavedValues['name'])->toBe('Test Family');
    });

    it('should save the family before creating the user', function (): void {
        // arrange
        $saveOrder = [];

        $familyInstance = Mockery::mock(Family::class);
        $familyInstance->allows('setAttribute');
        $familyInstance->allows('getAttribute');
        $familyInstance->shouldReceive('save')->twice()->andReturnUsing(function () use (&$saveOrder): bool {
            $saveOrder[] = 'family';

            return true;
        });

        $usersRelation = Mockery::mock(HasMany::class);
        $usersRelation->shouldReceive('save')->once()->andReturnUsing(function () use (&$saveOrder): bool {
            $saveOrder[] = 'user';

            return true;
        });

        $familyInstance->shouldReceive('users')->once()->andReturn($usersRelation);

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('newInstance')->withNoArgs()->andReturn($familyInstance);

        $userInstance = Mockery::mock(User::class);
        $userInstance->allows('setAttribute');
        $userInstance->allows('getAttribute');

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newInstance')->withNoArgs()->andReturn($userInstance);

        $action = new CreateUserWithFamilyAction($user, $family);
        $data = new class implements RegisterUserInterface
        {
            public string $familyName = 'Test Family';

            public string $name = 'Test User';

            public string $email = 'test@example.com';

            public string $password = 'password123';
        };

        // act
        $action->execute($data);

        // assert
        expect($saveOrder)->toBe(['family', 'user', 'family']);
    });

    it('should associate the user with the family via the relationship', function (): void {
        // arrange
        $familyInstance = Mockery::mock(Family::class);
        $familyInstance->allows('setAttribute');
        $familyInstance->allows('getAttribute');
        $familyInstance->shouldReceive('save')->twice();

        $userInstance = Mockery::mock(User::class);
        $userInstance->allows('setAttribute');
        $userInstance->allows('getAttribute');

        $usersRelation = Mockery::mock(HasMany::class);
        $usersRelation->shouldReceive('save')
            ->with($userInstance)
            ->once();

        $familyInstance->shouldReceive('users')->once()->andReturn($usersRelation);

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('newInstance')->withNoArgs()->andReturn($familyInstance);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newInstance')->withNoArgs()->andReturn($userInstance);

        $action = new CreateUserWithFamilyAction($user, $family);
        $data = new class implements RegisterUserInterface
        {
            public string $familyName = 'Test Family';

            public string $name = 'Test User';

            public string $email = 'test@example.com';

            public string $password = 'password123';
        };

        // act
        $action->execute($data);

        // assert - Mockery expectations verify the interactions
    });

    it('should set the correct user properties', function (): void {
        // arrange
        $familyInstance = Mockery::mock(Family::class);
        $familyInstance->allows('setAttribute');
        $familyInstance->allows('getAttribute');
        $familyInstance->shouldReceive('save')->twice();
        $familyInstance->shouldReceive('users->save')->once();

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('newInstance')->withNoArgs()->andReturn($familyInstance);

        $userSavedValues = [];
        $userInstance = Mockery::mock(User::class);
        $userInstance->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$userSavedValues): void {
            $userSavedValues[$key] = $value;
        });
        $userInstance->allows('getAttribute')->andReturnUsing(function ($key) use (&$userSavedValues): mixed {
            return $userSavedValues[$key] ?? null;
        });

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newInstance')
            ->withNoArgs()
            ->once()
            ->andReturn($userInstance);

        $action = new CreateUserWithFamilyAction($user, $family);
        $data = new class implements RegisterUserInterface
        {
            public string $familyName = 'Test Family';

            public string $name = 'John Doe';

            public string $email = 'john@example.com';

            public string $password = 'secret123';
        };

        // act
        $action->execute($data);

        // assert
        expect($userSavedValues['name'])->toBe('John Doe')
            ->and($userSavedValues['email'])->toBe('john@example.com')
            ->and($userSavedValues['password'])->toBe('secret123');
    });

    it('should set the created user as family head', function (): void {
        // arrange
        $familySavedValues = [];
        $familyInstance = Mockery::mock(Family::class);
        $familyInstance->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$familySavedValues): void {
            $familySavedValues[$key] = $value;
        });
        $familyInstance->allows('getAttribute')->andReturnUsing(function ($key) use (&$familySavedValues): mixed {
            return $familySavedValues[$key] ?? null;
        });
        $familyInstance->shouldReceive('save')->twice();

        $userInstance = Mockery::mock(User::class);
        $userInstance->allows('setAttribute');
        $userInstance->allows('getAttribute')->with('id')->andReturn(42);

        $usersRelation = Mockery::mock(HasMany::class);
        $usersRelation->shouldReceive('save')
            ->with($userInstance)
            ->once();

        $familyInstance->shouldReceive('users')->once()->andReturn($usersRelation);

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('newInstance')->withNoArgs()->andReturn($familyInstance);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newInstance')->withNoArgs()->andReturn($userInstance);

        $action = new CreateUserWithFamilyAction($user, $family);
        $data = new class implements RegisterUserInterface
        {
            public string $familyName = 'Test Family';

            public string $name = 'Test User';

            public string $email = 'test@example.com';

            public string $password = 'password123';
        };

        // act
        $action->execute($data);

        // assert
        expect($familySavedValues['head_id'])->toBe(42);
    });
});

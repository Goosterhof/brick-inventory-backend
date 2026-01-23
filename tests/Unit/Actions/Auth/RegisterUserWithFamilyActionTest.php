<?php

declare(strict_types=1);

use App\Actions\Auth\RegisterUserWithFamilyAction;
use App\Contracts\Auth\RegisterUserInterface;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

describe('RegisterUserWithFamilyAction', function (): void {
    it('should create a family with the provided name', function (): void {
        // arrange
        $familyInstance = Mockery::mock(Family::class)->makePartial();
        $familyInstance->shouldReceive('save')->once();
        $familyInstance->shouldReceive('users->save')->once();

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('newInstance')
            ->withNoArgs()
            ->once()
            ->andReturn($familyInstance);

        $userInstance = Mockery::mock(User::class)->makePartial();

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newInstance')
            ->withNoArgs()
            ->once()
            ->andReturn($userInstance);

        $action = new RegisterUserWithFamilyAction($user, $family);
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
            ->and($familyInstance->name)->toBe('Test Family');
    });

    it('should save the family before creating the user', function (): void {
        // arrange
        $saveOrder = [];

        $familyInstance = Mockery::mock(Family::class)->makePartial();
        $familyInstance->shouldReceive('save')->once()->andReturnUsing(function () use (&$saveOrder): bool {
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

        $userInstance = Mockery::mock(User::class)->makePartial();

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newInstance')->withNoArgs()->andReturn($userInstance);

        $action = new RegisterUserWithFamilyAction($user, $family);
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
        expect($saveOrder)->toBe(['family', 'user']);
    });

    it('should associate the user with the family via the relationship', function (): void {
        // arrange
        $familyInstance = Mockery::mock(Family::class)->makePartial();
        $familyInstance->shouldReceive('save')->once();

        $userInstance = Mockery::mock(User::class)->makePartial();

        $usersRelation = Mockery::mock(HasMany::class);
        $usersRelation->shouldReceive('save')
            ->with($userInstance)
            ->once();

        $familyInstance->shouldReceive('users')->once()->andReturn($usersRelation);

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('newInstance')->withNoArgs()->andReturn($familyInstance);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newInstance')->withNoArgs()->andReturn($userInstance);

        $action = new RegisterUserWithFamilyAction($user, $family);
        $data = new class implements RegisterUserInterface
        {
            public string $familyName = 'Test Family';

            public string $name = 'Test User';

            public string $email = 'test@example.com';

            public string $password = 'password123';
        };

        // act
        $action->execute($data);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });

    it('should set the correct user properties', function (): void {
        // arrange
        $familyInstance = Mockery::mock(Family::class)->makePartial();
        $familyInstance->shouldReceive('save')->once();
        $familyInstance->shouldReceive('users->save')->once();

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('newInstance')->withNoArgs()->andReturn($familyInstance);

        $userInstance = Mockery::mock(User::class)->makePartial();

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newInstance')
            ->withNoArgs()
            ->once()
            ->andReturn($userInstance);

        $action = new RegisterUserWithFamilyAction($user, $family);
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
        expect($userInstance->name)->toBe('John Doe')
            ->and($userInstance->email)->toBe('john@example.com')
            ->and($userInstance->password)->toBe('secret123');
    });
});

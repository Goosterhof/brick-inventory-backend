<?php

declare(strict_types=1);

use App\Actions\Auth\CreateUserWithFamilyAction;
use App\DataTransferObjects\RegisterUserData;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

describe('CreateUserWithFamilyAction', function (): void {
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

        $action = new CreateUserWithFamilyAction($user, $family);
        $data = new RegisterUserData(
            familyName: 'Test Family',
            name: 'Test User',
            email: 'test@example.com',
            password: 'password123',
        );

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

        $action = new CreateUserWithFamilyAction($user, $family);
        $data = new RegisterUserData(
            familyName: 'Test Family',
            name: 'Test User',
            email: 'test@example.com',
            password: 'password123',
        );

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

        $action = new CreateUserWithFamilyAction($user, $family);
        $data = new RegisterUserData(
            familyName: 'Test Family',
            name: 'Test User',
            email: 'test@example.com',
            password: 'password123',
        );

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

        $action = new CreateUserWithFamilyAction($user, $family);
        $data = new RegisterUserData(
            familyName: 'Test Family',
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secret123',
        );

        // act
        $action->execute($data);

        // assert
        expect($userInstance->name)->toBe('John Doe')
            ->and($userInstance->email)->toBe('john@example.com')
            ->and($userInstance->password)->toBe('secret123');
    });
});

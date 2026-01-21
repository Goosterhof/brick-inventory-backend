<?php

declare(strict_types=1);

use App\Actions\Auth\RegisterUserWithFamilyAction;
use App\DataTransferObjects\RegisterUserData;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

describe('RegisterUserWithFamilyAction', function (): void {
    it('should create a family with the provided name', function (): void {
        // arrange
        $familyInstance = Mockery::mock(Family::class);
        $familyInstance->shouldReceive('save')->once();
        $familyInstance->shouldReceive('users->save')->once();

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('newInstance')
            ->with(['name' => 'Test Family'])
            ->once()
            ->andReturn($familyInstance);

        $userInstance = Mockery::mock(User::class);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newInstance')
            ->with(Mockery::on(fn ($args): bool => $args['name'] === 'Test User'
                && $args['email'] === 'test@example.com'
                && $args['password'] === 'password123'))
            ->once()
            ->andReturn($userInstance);

        $action = new RegisterUserWithFamilyAction($user, $family);
        $data = new RegisterUserData(
            familyName: 'Test Family',
            name: 'Test User',
            email: 'test@example.com',
            password: 'password123',
        );

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($userInstance);
    });

    it('should save the family before creating the user', function (): void {
        // arrange
        $saveOrder = [];

        $familyInstance = Mockery::mock(Family::class);
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
        $family->shouldReceive('newInstance')->andReturn($familyInstance);

        $userInstance = Mockery::mock(User::class);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newInstance')->andReturn($userInstance);

        $action = new RegisterUserWithFamilyAction($user, $family);
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
        $familyInstance = Mockery::mock(Family::class);
        $familyInstance->shouldReceive('save')->once();

        $userInstance = Mockery::mock(User::class);

        $usersRelation = Mockery::mock(HasMany::class);
        $usersRelation->shouldReceive('save')
            ->with($userInstance)
            ->once();

        $familyInstance->shouldReceive('users')->once()->andReturn($usersRelation);

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('newInstance')->andReturn($familyInstance);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newInstance')->andReturn($userInstance);

        $action = new RegisterUserWithFamilyAction($user, $family);
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

    it('should pass the correct user data to newInstance', function (): void {
        // arrange
        $familyInstance = Mockery::mock(Family::class);
        $familyInstance->shouldReceive('save')->once();
        $familyInstance->shouldReceive('users->save')->once();

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('newInstance')->andReturn($familyInstance);

        $userInstance = Mockery::mock(User::class);

        $capturedData = null;
        $user = Mockery::mock(User::class);
        $user->shouldReceive('newInstance')
            ->once()
            ->andReturnUsing(function ($data) use (&$capturedData, $userInstance) {
                $capturedData = $data;

                return $userInstance;
            });

        $action = new RegisterUserWithFamilyAction($user, $family);
        $data = new RegisterUserData(
            familyName: 'Test Family',
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secret123',
        );

        // act
        $action->execute($data);

        // assert
        expect($capturedData)->toBe([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);
    });
});

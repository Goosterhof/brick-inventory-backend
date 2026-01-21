<?php

declare(strict_types=1);

use App\Actions\Auth\RegisterUserWithFamilyAction;
use App\DataTransferObjects\RegisterUserData;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('RegisterUserWithFamilyAction', function (): void {
    it('should create a user with a new family', function (): void {
        // arrange
        $action = new RegisterUserWithFamilyAction;
        $data = new RegisterUserData(
            familyName: 'Test Family',
            name: 'Test User',
            email: 'test@example.com',
            password: 'password123',
        );

        // act
        $user = $action->execute($data);

        // assert
        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBe('Test User')
            ->and($user->email)->toBe('test@example.com')
            ->and($user->family)->toBeInstanceOf(Family::class)
            ->and($user->family->name)->toBe('Test Family');
    });

    it('should hash the password', function (): void {
        // arrange
        $action = new RegisterUserWithFamilyAction;
        $data = new RegisterUserData(
            familyName: 'Test Family',
            name: 'Test User',
            email: 'test@example.com',
            password: 'password123',
        );

        // act
        $user = $action->execute($data);

        // assert
        expect($user->password)->not->toBe('password123');
    });

    it('should persist both family and user to database', function (): void {
        // arrange
        $action = new RegisterUserWithFamilyAction;
        $data = new RegisterUserData(
            familyName: 'Persisted Family',
            name: 'Persisted User',
            email: 'persisted@example.com',
            password: 'password123',
        );

        // act
        $action->execute($data);

        // assert
        expect(Family::where('name', 'Persisted Family')->exists())->toBeTrue()
            ->and(User::where('email', 'persisted@example.com')->exists())->toBeTrue();
    });

    it('should associate the user with the created family', function (): void {
        // arrange
        $action = new RegisterUserWithFamilyAction;
        $data = new RegisterUserData(
            familyName: 'Associated Family',
            name: 'Associated User',
            email: 'associated@example.com',
            password: 'password123',
        );

        // act
        $user = $action->execute($data);

        // assert
        $family = Family::where('name', 'Associated Family')->first();
        expect($user->family_id)->toBe($family->id);
    });
});

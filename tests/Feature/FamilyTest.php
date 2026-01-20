<?php

declare(strict_types=1);

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create a family', function (): void {
    $family = new Family;
    $family->name = 'Smith Family';
    $family->save();

    expect($family)->toBeInstanceOf(Family::class)
        ->and($family->name)->toBe('Smith Family');
});

test('can create a family using factory', function (): void {
    $family = Family::factory()->create();

    expect($family)->toBeInstanceOf(Family::class)
        ->and($family->name)->toBeString();
});

test('family can have multiple users', function (): void {
    $family = Family::factory()->create();
    $users = User::factory()->count(3)->create(['family_id' => $family->id]);

    expect($family->users)->toHaveCount(3)
        ->and($family->users->first())->toBeInstanceOf(User::class);
});

test('user belongs to a family', function (): void {
    $family = Family::factory()->create();
    $user = User::factory()->create(['family_id' => $family->id]);

    expect($user->family)->toBeInstanceOf(Family::class)
        ->and($user->family->id)->toBe($family->id);
});

test('user can exist without a family', function (): void {
    $user = User::factory()->create(['family_id' => null]);

    expect($user->family)->toBeNull();
});

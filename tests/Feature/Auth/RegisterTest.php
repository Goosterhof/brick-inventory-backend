<?php

declare(strict_types=1);

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register with family', function (): void {
    $response = $this->postJson('/api/register', [
        'family_name' => 'Smith Family',
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'family_id'],
            'token',
        ]);

    $this->assertDatabaseHas('families', ['name' => 'Smith Family']);
    $this->assertDatabaseHas('users', [
        'name' => 'John Smith',
        'email' => 'john@example.com',
    ]);

    $user = User::where('email', 'john@example.com')->first();
    expect($user->family)->toBeInstanceOf(Family::class)
        ->and($user->family->name)->toBe('Smith Family');
});

test('registration requires all fields', function (): void {
    $response = $this->postJson('/api/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['family_name', 'name', 'email', 'password']);
});

test('registration requires valid email', function (): void {
    $response = $this->postJson('/api/register', [
        'family_name' => 'Smith Family',
        'name' => 'John Smith',
        'email' => 'not-an-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('registration requires unique email', function (): void {
    $existingUser = new User;
    $existingUser->name = 'Existing User';
    $existingUser->email = 'john@example.com';
    $existingUser->password = 'password';
    $existingUser->save();

    $response = $this->postJson('/api/register', [
        'family_name' => 'Smith Family',
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('registration requires password confirmation', function (): void {
    $response = $this->postJson('/api/register', [
        'family_name' => 'Smith Family',
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('registration requires minimum password length', function (): void {
    $response = $this->postJson('/api/register', [
        'family_name' => 'Smith Family',
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

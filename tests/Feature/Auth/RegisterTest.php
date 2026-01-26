<?php

declare(strict_types=1);

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('RegisterController', function (): void {
    it('should register a user with a family', function (): void {
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

        $user = User::query()->where('email', 'john@example.com')->first();
        expect($user->family)->toBeInstanceOf(Family::class)
            ->and($user->family->name)->toBe('Smith Family');
    });

    it('should require all fields for registration', function (): void {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['family_name', 'name', 'email', 'password']);
    });

    it('should require a valid email', function (): void {
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

    it('should require a unique email', function (): void {
        User::factory()->create(['email' => 'john@example.com']);

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

    it('should require password confirmation', function (): void {
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

    it('should require minimum password length', function (): void {
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
});

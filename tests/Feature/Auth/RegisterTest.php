<?php

declare(strict_types=1);

use App\Models\Family;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

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
            ->assertJsonStructure(['id', 'name', 'email', 'family_id']);

        $this->assertDatabaseHas('families', ['name' => 'Smith Family']);
        $this->assertDatabaseHas('users', [
            'name' => 'John Smith',
            'email' => 'john@example.com',
        ]);

        $user = User::query()->where('email', 'john@example.com')->firstOrFail();
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

    it('should rate limit registration attempts', function (): void {
        RateLimiter::for('auth', fn (): Limit => Limit::perMinute(5));
        $this->freezeTime();

        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/register', [
                'family_name' => 'Family ' . $i,
                'name' => 'User ' . $i,
                'email' => sprintf('user%d@example.com', $i),
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])->assertStatus(201);

            // Reset auth state so next request is unauthenticated (same rate limiter key)
            auth()->guard('web')->logout();
            resolve('auth')->forgetGuards();
        }

        $response = $this->postJson('/api/register', [
            'family_name' => 'Family 6',
            'name' => 'User 6',
            'email' => 'user6@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(429);
    });
});

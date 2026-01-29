<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('FamilyController', function (): void {
    describe('setRebrickableToken', function (): void {
        it('should set the rebrickable user token', function (): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->putJson('/api/family/rebrickable-token', [
                'rebrickable_user_token' => 'my-secret-token',
            ]);

            $response->assertStatus(200)
                ->assertJson(['message' => 'Rebrickable user token configured successfully']);

            $user->family->refresh();
            expect($user->family->rebrickable_user_token)->toBe('my-secret-token');
        });

        it('should update existing rebrickable token', function (): void {
            $user = User::factory()->create();
            $user->family->rebrickable_user_token = 'old-token';
            $user->family->save();

            $response = $this->actingAs($user)->putJson('/api/family/rebrickable-token', [
                'rebrickable_user_token' => 'new-token',
            ]);

            $response->assertStatus(200);

            $user->family->refresh();
            expect($user->family->rebrickable_user_token)->toBe('new-token');
        });

        it('should return 401 when unauthenticated', function (): void {
            $response = $this->putJson('/api/family/rebrickable-token', [
                'rebrickable_user_token' => 'my-token',
            ]);

            $response->assertStatus(401);
        });

        it('should require rebrickable_user_token', function (): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->putJson('/api/family/rebrickable-token', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['rebrickable_user_token']);
        });

        it('should validate rebrickable_user_token is a string', function (): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->putJson('/api/family/rebrickable-token', [
                'rebrickable_user_token' => 12345,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['rebrickable_user_token']);
        });

        it('should validate rebrickable_user_token max length', function (): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->putJson('/api/family/rebrickable-token', [
                'rebrickable_user_token' => str_repeat('a', 256),
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['rebrickable_user_token']);
        });
    });
});

<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('LogoutController', function (): void {
    it('should logout an authenticated user', function (): void {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(204);
    });

    it('should return 401 for unauthenticated user', function (): void {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    });
});

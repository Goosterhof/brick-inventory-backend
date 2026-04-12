<?php

declare(strict_types = 1);

use App\Http\Controllers\InviteCodeController;
use App\Models\InviteCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(InviteCodeController::class);

uses(RefreshDatabase::class);

describe('InviteCodeController', function(): void {
    describe('store', function(): void {
        it('should generate an invite code for family head', function(): void {
            $headUser = User::factory()->create();

            $response = $this->actingAs($headUser)->postJson('/api/family/invite-code');

            $response->assertStatus(201)
                ->assertJsonStructure(['id', 'code', 'expires_at', 'created_at']);

            expect($response->json('code'))->toMatch('/^BRICK-[A-Z0-9]{4}$/');

            $this->assertDatabaseHas('invite_codes', [
                'family_id' => $headUser->family_id,
                'generated_by' => $headUser->id,
            ]);
        });

        it('should revoke existing active code when generating new one', function(): void {
            $headUser = User::factory()->create();

            $existingCode = InviteCode::factory()
                ->forFamily($headUser->family)
                ->generatedBy($headUser)
                ->create();

            $response = $this->actingAs($headUser)->postJson('/api/family/invite-code');

            $response->assertStatus(201);

            $existingCode->refresh();
            expect($existingCode->revoked_at)->not->toBeNull();

            $newCode = $response->json('code');
            expect($newCode)->not->toBe($existingCode->code);
        });

        it('should return 403 when non-head member tries to generate', function(): void {
            $headUser = User::factory()->create();
            $member = User::factory()->forFamily($headUser->family)->create();

            $response = $this->actingAs($member)->postJson('/api/family/invite-code');

            $response->assertStatus(403);
        });

        it('should return 401 when unauthenticated', function(): void {
            $response = $this->postJson('/api/family/invite-code');

            $response->assertStatus(401);
        });

        it('should set expires_at based on configured TTL', function(): void {
            $this->freezeTime();
            $headUser = User::factory()->create();

            $response = $this->actingAs($headUser)->postJson('/api/family/invite-code');

            $response->assertStatus(201);

            $inviteCode = InviteCode::query()->where('code', $response->json('code'))->firstOrFail();
            expect($inviteCode->expires_at->toDateTimeString())
                ->toBe(now()->addDays((int) config('app.invite_code_ttl_days'))->toDateTimeString());
        });
    });

    describe('show', function(): void {
        it('should return the active invite code', function(): void {
            $headUser = User::factory()->create();
            $code = InviteCode::factory()
                ->forFamily($headUser->family)
                ->generatedBy($headUser)
                ->create();

            $response = $this->actingAs($headUser)->getJson('/api/family/invite-code');

            $response->assertStatus(200)
                ->assertJsonPath('code', $code->code)
                ->assertJsonStructure(['id', 'code', 'expires_at', 'created_at']);
        });

        it('should return 404 when no active code exists', function(): void {
            $headUser = User::factory()->create();

            $response = $this->actingAs($headUser)->getJson('/api/family/invite-code');

            $response->assertStatus(404)
                ->assertJsonPath('error', 'No active invite code found');
        });

        it('should return 404 when code is expired', function(): void {
            $headUser = User::factory()->create();
            InviteCode::factory()
                ->forFamily($headUser->family)
                ->generatedBy($headUser)
                ->expired()
                ->create();

            $response = $this->actingAs($headUser)->getJson('/api/family/invite-code');

            $response->assertStatus(404);
        });

        it('should return 404 when code is revoked', function(): void {
            $headUser = User::factory()->create();
            InviteCode::factory()
                ->forFamily($headUser->family)
                ->generatedBy($headUser)
                ->revoked()
                ->create();

            $response = $this->actingAs($headUser)->getJson('/api/family/invite-code');

            $response->assertStatus(404);
        });

        it('should return 403 when non-head member tries to view', function(): void {
            $headUser = User::factory()->create();
            $member = User::factory()->forFamily($headUser->family)->create();

            $response = $this->actingAs($member)->getJson('/api/family/invite-code');

            $response->assertStatus(403);
        });

        it('should return 401 when unauthenticated', function(): void {
            $response = $this->getJson('/api/family/invite-code');

            $response->assertStatus(401);
        });
    });

    describe('destroy', function(): void {
        it('should revoke the active invite code', function(): void {
            $headUser = User::factory()->create();
            $code = InviteCode::factory()
                ->forFamily($headUser->family)
                ->generatedBy($headUser)
                ->create();

            $response = $this->actingAs($headUser)->deleteJson('/api/family/invite-code');

            $response->assertStatus(204);

            $code->refresh();
            expect($code->revoked_at)->not->toBeNull();
        });

        it('should return 404 when no active code exists to revoke', function(): void {
            $headUser = User::factory()->create();

            $response = $this->actingAs($headUser)->deleteJson('/api/family/invite-code');

            $response->assertStatus(404)
                ->assertJsonPath('error', 'No active invite code found');
        });

        it('should return 403 when non-head member tries to revoke', function(): void {
            $headUser = User::factory()->create();
            $member = User::factory()->forFamily($headUser->family)->create();

            $response = $this->actingAs($member)->deleteJson('/api/family/invite-code');

            $response->assertStatus(403);
        });

        it('should return 401 when unauthenticated', function(): void {
            $response = $this->deleteJson('/api/family/invite-code');

            $response->assertStatus(401);
        });
    });
});

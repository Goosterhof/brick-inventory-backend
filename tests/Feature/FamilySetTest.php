<?php

declare(strict_types=1);

use App\Enums\FamilySetStatus;
use App\Models\Family;
use App\Models\FamilySet;
use App\Models\Set;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

describe('FamilySetController', function (): void {
    describe('index', function (): void {
        it('should return empty list when family has no sets', function (): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->getJson('/api/family-sets');

            $response->assertStatus(200)
                ->assertJsonCount(0);
        });

        it('should return family sets for authenticated user', function (): void {
            $user = User::factory()->create();
            $set = Set::factory()->create(['set_num' => '75192-1', 'name' => 'Millennium Falcon']);

            FamilySet::factory()->create([
                'family_id' => $user->family_id,
                'set_id' => $set->id,
                'quantity' => 2,
                'status' => FamilySetStatus::Built,
            ]);

            $response = $this->actingAs($user)->getJson('/api/family-sets');

            $response->assertStatus(200)
                ->assertJsonCount(1)
                ->assertJsonPath('0.quantity', 2)
                ->assertJsonPath('0.status', 'built')
                ->assertJsonPath('0.set.set_num', '75192-1');
        });

        it('should not return sets from other families', function (): void {
            $user = User::factory()->create();
            $otherFamily = Family::factory()->create();
            $set = Set::factory()->create();

            FamilySet::factory()->create([
                'family_id' => $otherFamily->id,
                'set_id' => $set->id,
            ]);

            $response = $this->actingAs($user)->getJson('/api/family-sets');

            $response->assertStatus(200)
                ->assertJsonCount(0);
        });

        it('should require authentication', function (): void {
            $response = $this->getJson('/api/family-sets');

            $response->assertStatus(401);
        });
    });

    describe('store', function (): void {
        it('should add an existing set to family', function (): void {
            $user = User::factory()->create();
            $set = Set::factory()->create(['set_num' => '75192-1', 'name' => 'Millennium Falcon']);

            $response = $this->actingAs($user)->postJson('/api/family-sets', [
                'set_num' => '75192-1',
                'quantity' => 2,
                'status' => 'sealed',
                'purchase_date' => '2024-01-15',
                'notes' => 'Birthday gift',
            ]);

            $response->assertStatus(201)
                ->assertJsonPath('quantity', 2)
                ->assertJsonPath('status', 'sealed')
                ->assertJsonPath('purchase_date', '2024-01-15')
                ->assertJsonPath('notes', 'Birthday gift')
                ->assertJsonPath('set.set_num', '75192-1');

            $this->assertDatabaseHas('family_sets', [
                'family_id' => $user->family_id,
                'set_id' => $set->id,
                'quantity' => 2,
            ]);
        });

        it('should fetch set from rebrickable if not in database', function (): void {
            Http::fake([
                'rebrickable.com/api/v3/lego/sets/10281-1/' => Http::response([
                    'set_num' => '10281-1',
                    'name' => 'Bonsai Tree',
                    'year' => 2021,
                    'theme_id' => 598,
                    'num_parts' => 878,
                    'set_img_url' => 'https://example.com/bonsai.jpg',
                ]),
            ]);

            $user = User::factory()->create();

            $response = $this->actingAs($user)->postJson('/api/family-sets', [
                'set_num' => '10281-1',
            ]);

            $response->assertStatus(201)
                ->assertJsonPath('set.set_num', '10281-1')
                ->assertJsonPath('set.name', 'Bonsai Tree');

            $this->assertDatabaseHas('sets', ['set_num' => '10281-1']);
            $this->assertDatabaseHas('family_sets', [
                'family_id' => $user->family_id,
            ]);
        });

        it('should use default values when not provided', function (): void {
            $user = User::factory()->create();
            Set::factory()->create(['set_num' => '75192-1']);

            $response = $this->actingAs($user)->postJson('/api/family-sets', [
                'set_num' => '75192-1',
            ]);

            $response->assertStatus(201)
                ->assertJsonPath('quantity', 1)
                ->assertJsonPath('status', 'sealed')
                ->assertJsonPath('purchase_date', null)
                ->assertJsonPath('notes', null);
        });

        it('should return 404 for non-existent set from rebrickable', function (): void {
            Http::fake([
                'rebrickable.com/api/v3/lego/sets/99999-1/' => Http::response(
                    ['detail' => 'Not found.'],
                    404,
                ),
            ]);

            $user = User::factory()->create();

            $response = $this->actingAs($user)->postJson('/api/family-sets', [
                'set_num' => '99999-1',
            ]);

            $response->assertStatus(404)
                ->assertJson(['error' => 'Set not found']);
        });

        it('should validate required fields', function (): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->postJson('/api/family-sets', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['set_num']);
        });

        it('should validate status enum', function (): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->postJson('/api/family-sets', [
                'set_num' => '75192-1',
                'status' => 'invalid_status',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
        });

        it('should allow adding the same set multiple times', function (): void {
            $user = User::factory()->create();
            $set = Set::factory()->create(['set_num' => '75192-1']);

            FamilySet::factory()->create([
                'family_id' => $user->family_id,
                'set_id' => $set->id,
            ]);

            $response = $this->actingAs($user)->postJson('/api/family-sets', [
                'set_num' => '75192-1',
            ]);

            $response->assertStatus(201);

            expect(FamilySet::where('family_id', $user->family_id)->count())->toBe(2);
        });
    });

    describe('show', function (): void {
        it('should return a family set', function (): void {
            $user = User::factory()->create();
            $set = Set::factory()->create(['set_num' => '75192-1', 'name' => 'Millennium Falcon']);
            $familySet = FamilySet::factory()->create([
                'family_id' => $user->family_id,
                'set_id' => $set->id,
                'status' => FamilySetStatus::Built,
            ]);

            $response = $this->actingAs($user)->getJson('/api/family-sets/' . $familySet->id);

            $response->assertStatus(200)
                ->assertJsonPath('id', $familySet->id)
                ->assertJsonPath('status', 'built')
                ->assertJsonPath('set.set_num', '75192-1');
        });

        it('should return 404 for family set from another family', function (): void {
            $user = User::factory()->create();
            $otherFamily = Family::factory()->create();
            $set = Set::factory()->create();
            $familySet = FamilySet::factory()->create([
                'family_id' => $otherFamily->id,
                'set_id' => $set->id,
            ]);

            $response = $this->actingAs($user)->getJson('/api/family-sets/' . $familySet->id);

            $response->assertStatus(404);
        });
    });

    describe('update', function (): void {
        it('should update a family set', function (): void {
            $user = User::factory()->create();
            $set = Set::factory()->create();
            $familySet = FamilySet::factory()->create([
                'family_id' => $user->family_id,
                'set_id' => $set->id,
                'quantity' => 1,
                'status' => FamilySetStatus::Sealed,
            ]);

            $response = $this->actingAs($user)->patchJson('/api/family-sets/' . $familySet->id, [
                'quantity' => 3,
                'status' => 'built',
                'notes' => 'Updated notes',
            ]);

            $response->assertStatus(200)
                ->assertJsonPath('quantity', 3)
                ->assertJsonPath('status', 'built')
                ->assertJsonPath('notes', 'Updated notes');

            $this->assertDatabaseHas('family_sets', [
                'id' => $familySet->id,
                'quantity' => 3,
                'status' => 'built',
                'notes' => 'Updated notes',
            ]);
        });

        it('should require quantity and status', function (): void {
            $user = User::factory()->create();
            $set = Set::factory()->create();
            $familySet = FamilySet::factory()->create([
                'family_id' => $user->family_id,
                'set_id' => $set->id,
            ]);

            $response = $this->actingAs($user)->patchJson('/api/family-sets/' . $familySet->id, [
                'notes' => 'Just notes',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['quantity', 'status']);
        });

        it('should return 404 for family set from another family', function (): void {
            $user = User::factory()->create();
            $otherFamily = Family::factory()->create();
            $set = Set::factory()->create();
            $familySet = FamilySet::factory()->create([
                'family_id' => $otherFamily->id,
                'set_id' => $set->id,
            ]);

            $response = $this->actingAs($user)->patchJson('/api/family-sets/' . $familySet->id, [
                'quantity' => 5,
                'status' => 'built',
            ]);

            $response->assertStatus(404);
        });
    });

    describe('destroy', function (): void {
        it('should delete a family set', function (): void {
            $user = User::factory()->create();
            $set = Set::factory()->create();
            $familySet = FamilySet::factory()->create([
                'family_id' => $user->family_id,
                'set_id' => $set->id,
            ]);

            $response = $this->actingAs($user)->deleteJson('/api/family-sets/' . $familySet->id);

            $response->assertStatus(204);

            $this->assertDatabaseMissing('family_sets', ['id' => $familySet->id]);
        });

        it('should return 404 for family set from another family', function (): void {
            $user = User::factory()->create();
            $otherFamily = Family::factory()->create();
            $set = Set::factory()->create();
            $familySet = FamilySet::factory()->create([
                'family_id' => $otherFamily->id,
                'set_id' => $set->id,
            ]);

            $response = $this->actingAs($user)->deleteJson('/api/family-sets/' . $familySet->id);

            $response->assertStatus(404);

            $this->assertDatabaseHas('family_sets', ['id' => $familySet->id]);
        });
    });
});

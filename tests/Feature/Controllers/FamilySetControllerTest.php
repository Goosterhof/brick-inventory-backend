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
                ->assertJsonPath('0.set_id', $set->id);
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

        it('should return 401 when unauthenticated', function (): void {
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
                ->assertJsonPath('set_id', $set->id);

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

            $response->assertStatus(201);

            $this->assertDatabaseHas('sets', ['set_num' => '10281-1']);
            $createdSet = Set::query()->where('set_num', '10281-1')->firstOrFail();
            $response->assertJsonPath('set_id', $createdSet->id);
            $this->assertDatabaseHas('family_sets', [
                'family_id' => $user->family_id,
                'set_id' => $createdSet->id,
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

        it('should return 401 when unauthenticated', function (): void {
            $response = $this->postJson('/api/family-sets', [
                'set_num' => '75192-1',
            ]);

            $response->assertStatus(401);
        });

        it('should require set_num', function (): void {
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

            expect(FamilySet::query()->where('family_id', $user->family_id)->count())->toBe(2);
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
                ->assertJsonPath('set_id', $set->id);
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

        it('should return 401 when unauthenticated', function (): void {
            $set = Set::factory()->create();
            $familySet = FamilySet::factory()->create([
                'set_id' => $set->id,
            ]);

            $response = $this->getJson('/api/family-sets/' . $familySet->id);

            $response->assertStatus(401);
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

        it('should return 401 when unauthenticated', function (): void {
            $set = Set::factory()->create();
            $familySet = FamilySet::factory()->create([
                'set_id' => $set->id,
            ]);

            $response = $this->patchJson('/api/family-sets/' . $familySet->id, [
                'quantity' => 5,
                'status' => 'built',
            ]);

            $response->assertStatus(401);
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

        it('should return 401 when unauthenticated', function (): void {
            $set = Set::factory()->create();
            $familySet = FamilySet::factory()->create([
                'set_id' => $set->id,
            ]);

            $response = $this->deleteJson('/api/family-sets/' . $familySet->id);

            $response->assertStatus(401);
        });
    });

    describe('importFromRebrickable', function (): void {
        it('should import sets from rebrickable user collection', function (): void {
            Http::fake([
                'rebrickable.com/api/v3/users/test-user-token/sets/' => Http::response([
                    'results' => [
                        [
                            'set' => [
                                'set_num' => '75192-1',
                                'name' => 'Millennium Falcon',
                                'year' => 2017,
                                'theme_id' => 158,
                                'num_parts' => 7541,
                                'set_img_url' => 'https://example.com/75192.jpg',
                            ],
                            'quantity' => 2,
                        ],
                        [
                            'set' => [
                                'set_num' => '10281-1',
                                'name' => 'Bonsai Tree',
                                'year' => 2021,
                                'theme_id' => 598,
                                'num_parts' => 878,
                                'set_img_url' => null,
                            ],
                            'quantity' => 1,
                        ],
                    ],
                    'next' => null,
                ]),
            ]);

            $user = User::factory()->create();
            $user->family->rebrickable_user_token = 'test-user-token';
            $user->family->save();

            $response = $this->actingAs($user)->postJson('/api/family-sets/import-from-rebrickable');

            $response->assertStatus(200)
                ->assertJsonPath('message', 'Import completed successfully')
                ->assertJsonPath('created', 2)
                ->assertJsonPath('updated', 0)
                ->assertJsonPath('skipped', 0)
                ->assertJsonPath('total', 2)
                ->assertJsonPath('complete', true);

            $this->assertDatabaseHas('sets', ['set_num' => '75192-1']);
            $this->assertDatabaseHas('sets', ['set_num' => '10281-1']);
            $this->assertDatabaseHas('family_sets', [
                'family_id' => $user->family_id,
                'quantity' => 2,
            ]);
            $this->assertDatabaseHas('family_sets', [
                'family_id' => $user->family_id,
                'quantity' => 1,
            ]);
        });

        it('should update existing family sets on import', function (): void {
            $user = User::factory()->create();
            $user->family->rebrickable_user_token = 'test-user-token';
            $user->family->save();

            $set = Set::factory()->create(['set_num' => '75192-1', 'name' => 'Millennium Falcon']);
            FamilySet::factory()->create([
                'family_id' => $user->family_id,
                'set_id' => $set->id,
                'quantity' => 1,
            ]);

            Http::fake([
                'rebrickable.com/api/v3/users/test-user-token/sets/' => Http::response([
                    'results' => [
                        [
                            'set' => [
                                'set_num' => '75192-1',
                                'name' => 'Millennium Falcon',
                                'year' => 2017,
                                'theme_id' => 158,
                                'num_parts' => 7541,
                                'set_img_url' => null,
                            ],
                            'quantity' => 3,
                        ],
                    ],
                    'next' => null,
                ]),
            ]);

            $response = $this->actingAs($user)->postJson('/api/family-sets/import-from-rebrickable');

            $response->assertStatus(200)
                ->assertJsonPath('created', 0)
                ->assertJsonPath('updated', 1)
                ->assertJsonPath('skipped', 0)
                ->assertJsonPath('total', 1)
                ->assertJsonPath('complete', true);

            $this->assertDatabaseHas('family_sets', [
                'family_id' => $user->family_id,
                'set_id' => $set->id,
                'quantity' => 3,
            ]);
        });

        it('should skip sets with duplicate family set entries', function (): void {
            $user = User::factory()->create();
            $user->family->rebrickable_user_token = 'test-user-token';
            $user->family->save();

            $set = Set::factory()->create(['set_num' => '75192-1', 'name' => 'Millennium Falcon']);
            // Create two family sets for the same set (duplicates)
            FamilySet::factory()->create([
                'family_id' => $user->family_id,
                'set_id' => $set->id,
                'quantity' => 1,
                'status' => FamilySetStatus::Sealed,
            ]);
            FamilySet::factory()->create([
                'family_id' => $user->family_id,
                'set_id' => $set->id,
                'quantity' => 1,
                'status' => FamilySetStatus::Built,
            ]);

            Http::fake([
                'rebrickable.com/api/v3/users/test-user-token/sets/' => Http::response([
                    'results' => [
                        [
                            'set' => [
                                'set_num' => '75192-1',
                                'name' => 'Millennium Falcon',
                                'year' => 2017,
                                'theme_id' => 158,
                                'num_parts' => 7541,
                                'set_img_url' => null,
                            ],
                            'quantity' => 3,
                        ],
                    ],
                    'next' => null,
                ]),
            ]);

            $response = $this->actingAs($user)->postJson('/api/family-sets/import-from-rebrickable');

            $response->assertStatus(200)
                ->assertJsonPath('created', 0)
                ->assertJsonPath('updated', 0)
                ->assertJsonPath('skipped', 1)
                ->assertJsonPath('total', 0)
                ->assertJsonPath('complete', true)
                ->assertJsonPath('skipped_set_nums', ['75192-1']);

            // Verify neither family set was modified
            $this->assertDatabaseHas('family_sets', [
                'family_id' => $user->family_id,
                'set_id' => $set->id,
                'quantity' => 1,
                'status' => 'sealed',
            ]);
            $this->assertDatabaseHas('family_sets', [
                'family_id' => $user->family_id,
                'set_id' => $set->id,
                'quantity' => 1,
                'status' => 'built',
            ]);
        });

        it('should return 400 when rebrickable token is not configured', function (): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->postJson('/api/family-sets/import-from-rebrickable');

            $response->assertStatus(400)
                ->assertJson(['error' => 'Rebrickable user token not configured']);
        });

        it('should return 403 when non-head family member tries to import', function (): void {
            $headUser = User::factory()->create();
            $memberUser = User::factory()->forFamily($headUser->family)->create();

            $response = $this->actingAs($memberUser)->postJson('/api/family-sets/import-from-rebrickable');

            $response->assertStatus(403);
        });

        it('should return 401 when unauthenticated', function (): void {
            $response = $this->postJson('/api/family-sets/import-from-rebrickable');

            $response->assertStatus(401);
        });

        it('should handle empty rebrickable collection', function (): void {
            Http::fake([
                'rebrickable.com/api/v3/users/test-user-token/sets/' => Http::response([
                    'results' => [],
                    'next' => null,
                ]),
            ]);

            $user = User::factory()->create();
            $user->family->rebrickable_user_token = 'test-user-token';
            $user->family->save();

            $response = $this->actingAs($user)->postJson('/api/family-sets/import-from-rebrickable');

            $response->assertStatus(200)
                ->assertJsonPath('created', 0)
                ->assertJsonPath('updated', 0)
                ->assertJsonPath('skipped', 0)
                ->assertJsonPath('total', 0)
                ->assertJsonPath('complete', true);
        });

        it('should report partial import when API fails after first page', function (): void {
            Http::fake([
                'rebrickable.com/api/v3/users/test-user-token/sets/' => Http::response([
                    'results' => [
                        [
                            'set' => [
                                'set_num' => '75192-1',
                                'name' => 'Millennium Falcon',
                                'year' => 2017,
                                'theme_id' => 158,
                                'num_parts' => 7541,
                                'set_img_url' => null,
                            ],
                            'quantity' => 1,
                        ],
                    ],
                    'next' => 'https://rebrickable.com/api/v3/users/test-user-token/sets/?page=2',
                ]),
                'rebrickable.com/api/v3/users/test-user-token/sets/?page=2' => Http::response([], 500),
            ]);

            $user = User::factory()->create();
            $user->family->rebrickable_user_token = 'test-user-token';
            $user->family->save();

            $response = $this->actingAs($user)->postJson('/api/family-sets/import-from-rebrickable');

            $response->assertStatus(200)
                ->assertJsonPath('created', 1)
                ->assertJsonPath('total', 1)
                ->assertJsonPath('complete', false)
                ->assertJsonStructure(['error']);

            expect($response->json('message'))->toContain('partially completed');
            expect($response->json('error'))->toContain('Import incomplete');

            // Verify page 1 data was saved
            $this->assertDatabaseHas('sets', ['set_num' => '75192-1']);
            $this->assertDatabaseHas('family_sets', [
                'family_id' => $user->family_id,
                'quantity' => 1,
            ]);
        });

        it('should handle rebrickable API errors', function (): void {
            Http::fake([
                'rebrickable.com/api/v3/users/invalid-token/sets/' => Http::response([], 401),
            ]);

            $user = User::factory()->create();
            $user->family->rebrickable_user_token = 'invalid-token';
            $user->family->save();

            $response = $this->actingAs($user)->postJson('/api/family-sets/import-from-rebrickable');

            $response->assertStatus(401)
                ->assertJson(['error' => 'Invalid API key']);
        });
    });
});

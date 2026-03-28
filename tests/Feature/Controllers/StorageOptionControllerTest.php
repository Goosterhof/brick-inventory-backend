<?php

declare(strict_types=1);

use App\Http\Controllers\StorageOptionController;
use App\Models\Part;
use App\Models\StorageOption;
use App\Models\StorageOptionPart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(StorageOptionController::class);

uses(RefreshDatabase::class);

describe('StorageOptionController', function (): void {
    describe('index', function (): void {
        it('should return storage options for the authenticated user family', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create([
                'family_id' => $user->family_id,
                'name' => 'Cabinet 1',
            ]);

            $response = $this->actingAs($user)->getJson('/api/storage-options');

            $response->assertStatus(200)
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.name', 'Cabinet 1')
                ->assertJsonStructure(['data', 'path', 'per_page', 'next_cursor', 'next_page_url', 'prev_cursor', 'prev_page_url']);
        });

        it('should not return storage options from other families', function (): void {
            $user = User::factory()->create();
            StorageOption::factory()->create(['name' => 'Other Family Cabinet']);

            $response = $this->actingAs($user)->getJson('/api/storage-options');

            $response->assertStatus(200)
                ->assertJsonCount(0, 'data');
        });

        it('should return 401 when unauthenticated', function (): void {
            $response = $this->getJson('/api/storage-options');

            $response->assertStatus(401);
        });

        it('should return child_ids for nested children', function (): void {
            $user = User::factory()->create();
            $cabinet = StorageOption::factory()->create([
                'family_id' => $user->family_id,
                'name' => 'Cabinet 1',
            ]);
            $drawer = StorageOption::factory()->create([
                'family_id' => $user->family_id,
                'parent_id' => $cabinet->id,
                'name' => 'Drawer A1',
            ]);

            $response = $this->actingAs($user)->getJson('/api/storage-options');

            $response->assertStatus(200)
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.child_ids.0', $drawer->id);
        });

        it('should use default per_page of 25', function (): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->getJson('/api/storage-options');

            $response->assertStatus(200)
                ->assertJsonPath('per_page', 25);
        });

        it('should accept custom per_page', function (): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->getJson('/api/storage-options?per_page=10');

            $response->assertStatus(200)
                ->assertJsonPath('per_page', 10);
        });

        it('should cap per_page at 100', function (): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->getJson('/api/storage-options?per_page=200');

            $response->assertStatus(200)
                ->assertJsonPath('per_page', 100);
        });

        it('should paginate results with cursor navigation', function (): void {
            $user = User::factory()->create();

            StorageOption::factory()->count(3)->create([
                'family_id' => $user->family_id,
                'parent_id' => null,
            ]);

            $firstPage = $this->actingAs($user)->getJson('/api/storage-options?per_page=2');

            $firstPage->assertStatus(200)
                ->assertJsonCount(2, 'data');

            $nextCursor = $firstPage->json('next_cursor');
            expect($nextCursor)->not->toBeNull();

            $secondPage = $this->actingAs($user)->getJson('/api/storage-options?per_page=2&cursor=' . $nextCursor);

            $secondPage->assertStatus(200)
                ->assertJsonCount(1, 'data');
            expect($secondPage->json('next_cursor'))->toBeNull();
        });
    });

    describe('store', function (): void {
        it('should create a storage option', function (): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->postJson('/api/storage-options', [
                'name' => 'New Cabinet',
                'description' => 'A test cabinet',
            ]);

            $response->assertStatus(201)
                ->assertJsonPath('name', 'New Cabinet')
                ->assertJsonPath('description', 'A test cabinet');

            $this->assertDatabaseHas('storage_options', [
                'family_id' => $user->family_id,
                'name' => 'New Cabinet',
            ]);
        });

        it('should create a storage option with parent', function (): void {
            $user = User::factory()->create();
            $cabinet = StorageOption::factory()->create([
                'family_id' => $user->family_id,
            ]);

            $response = $this->actingAs($user)->postJson('/api/storage-options', [
                'name' => 'Drawer A1',
                'parent_id' => $cabinet->id,
                'row' => 1,
                'column' => 1,
            ]);

            $response->assertStatus(201)
                ->assertJsonPath('parent_id', $cabinet->id)
                ->assertJsonPath('row', 1)
                ->assertJsonPath('column', 1);
        });

        it('should return 401 when unauthenticated', function (): void {
            $response = $this->postJson('/api/storage-options', [
                'name' => 'New Cabinet',
            ]);

            $response->assertStatus(401);
        });

        it('should require name', function (): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->postJson('/api/storage-options', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('should return 422 when parent_id belongs to another family', function (): void {
            $user = User::factory()->create();
            $otherFamilyOption = StorageOption::factory()->create();

            $response = $this->actingAs($user)->postJson('/api/storage-options', [
                'name' => 'Child Drawer',
                'parent_id' => $otherFamilyOption->id,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['parent_id']);
        });
    });

    describe('show', function (): void {
        it('should return a storage option', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create([
                'family_id' => $user->family_id,
                'name' => 'Cabinet 1',
            ]);

            $response = $this->actingAs($user)->getJson('/api/storage-options/' . $storageOption->id);

            $response->assertStatus(200)
                ->assertJsonPath('name', 'Cabinet 1');
        });

        it('should return 404 for storage option from another family', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create(['name' => 'Other Family Cabinet']);

            $response = $this->actingAs($user)->getJson('/api/storage-options/' . $storageOption->id);

            $response->assertStatus(404);
        });

        it('should return 401 when unauthenticated', function (): void {
            $storageOption = StorageOption::factory()->create();

            $response = $this->getJson('/api/storage-options/' . $storageOption->id);

            $response->assertStatus(401);
        });
    });

    describe('update', function (): void {
        it('should update a storage option', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create([
                'family_id' => $user->family_id,
                'name' => 'Old Name',
            ]);

            $response = $this->actingAs($user)->putJson('/api/storage-options/' . $storageOption->id, [
                'name' => 'New Name',
                'description' => 'Updated description',
            ]);

            $response->assertStatus(200)
                ->assertJsonPath('name', 'New Name')
                ->assertJsonPath('description', 'Updated description');

            $this->assertDatabaseHas('storage_options', [
                'id' => $storageOption->id,
                'name' => 'New Name',
                'description' => 'Updated description',
            ]);
        });

        it('should return 404 for storage option from another family', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create(['name' => 'Other Family Cabinet']);

            $response = $this->actingAs($user)->putJson('/api/storage-options/' . $storageOption->id, [
                'name' => 'Hacked Name',
            ]);

            $response->assertStatus(404);
        });

        it('should return 401 when unauthenticated', function (): void {
            $storageOption = StorageOption::factory()->create();

            $response = $this->putJson('/api/storage-options/' . $storageOption->id, [
                'name' => 'New Name',
            ]);

            $response->assertStatus(401);
        });
    });

    describe('destroy', function (): void {
        it('should delete a storage option', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create([
                'family_id' => $user->family_id,
            ]);

            $response = $this->actingAs($user)->deleteJson('/api/storage-options/' . $storageOption->id);

            $response->assertStatus(204);
            $this->assertDatabaseMissing('storage_options', ['id' => $storageOption->id]);
        });

        it('should return 404 for storage option from another family', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create();

            $response = $this->actingAs($user)->deleteJson('/api/storage-options/' . $storageOption->id);

            $response->assertStatus(404);
            $this->assertDatabaseHas('storage_options', ['id' => $storageOption->id]);
        });

        it('should return 401 when unauthenticated', function (): void {
            $storageOption = StorageOption::factory()->create();

            $response = $this->deleteJson('/api/storage-options/' . $storageOption->id);

            $response->assertStatus(401);
        });

        it('should cascade delete children', function (): void {
            $user = User::factory()->create();
            $cabinet = StorageOption::factory()->create([
                'family_id' => $user->family_id,
            ]);
            $drawer = StorageOption::factory()->create([
                'family_id' => $user->family_id,
                'parent_id' => $cabinet->id,
            ]);

            $response = $this->actingAs($user)->deleteJson('/api/storage-options/' . $cabinet->id);

            $response->assertStatus(204);
            $this->assertDatabaseMissing('storage_options', ['id' => $cabinet->id]);
            $this->assertDatabaseMissing('storage_options', ['id' => $drawer->id]);
        });
    });

    describe('parts', function (): void {
        it('should return parts assigned to storage option', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create([
                'family_id' => $user->family_id,
            ]);
            $part = Part::factory()->create();
            StorageOptionPart::factory()->create([
                'storage_option_id' => $storageOption->id,
                'part_id' => $part->id,
                'quantity' => 50,
            ]);

            $response = $this->actingAs($user)->getJson(sprintf('/api/storage-options/%s/parts', $storageOption->id));

            $response->assertStatus(200)
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.quantity', 50)
                ->assertJsonStructure(['data', 'path', 'per_page', 'next_cursor', 'next_page_url', 'prev_cursor', 'prev_page_url']);
        });

        it('should return 404 for storage option from another family', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create();

            $response = $this->actingAs($user)->getJson(sprintf('/api/storage-options/%s/parts', $storageOption->id));

            $response->assertStatus(404);
        });

        it('should return 401 when unauthenticated', function (): void {
            $storageOption = StorageOption::factory()->create();

            $response = $this->getJson(sprintf('/api/storage-options/%s/parts', $storageOption->id));

            $response->assertStatus(401);
        });

        it('should use default per_page of 25', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create([
                'family_id' => $user->family_id,
            ]);

            $response = $this->actingAs($user)->getJson(sprintf('/api/storage-options/%s/parts', $storageOption->id));

            $response->assertStatus(200)
                ->assertJsonPath('per_page', 25);
        });

        it('should cap per_page at 100', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create([
                'family_id' => $user->family_id,
            ]);

            $response = $this->actingAs($user)->getJson(sprintf('/api/storage-options/%s/parts?per_page=200', $storageOption->id));

            $response->assertStatus(200)
                ->assertJsonPath('per_page', 100);
        });

        it('should paginate results with cursor navigation', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create([
                'family_id' => $user->family_id,
            ]);

            StorageOptionPart::factory()->count(3)->create([
                'storage_option_id' => $storageOption->id,
            ]);

            $firstPage = $this->actingAs($user)->getJson(sprintf('/api/storage-options/%s/parts?per_page=2', $storageOption->id));

            $firstPage->assertStatus(200)
                ->assertJsonCount(2, 'data');

            $nextCursor = $firstPage->json('next_cursor');
            expect($nextCursor)->not->toBeNull();

            $secondPage = $this->actingAs($user)->getJson(sprintf('/api/storage-options/%s/parts?per_page=2&cursor=%s', $storageOption->id, $nextCursor));

            $secondPage->assertStatus(200)
                ->assertJsonCount(1, 'data');
            expect($secondPage->json('next_cursor'))->toBeNull();
        });
    });

    describe('assignPart', function (): void {
        it('should assign a part to a storage option', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create([
                'family_id' => $user->family_id,
            ]);
            $part = Part::factory()->create();

            $response = $this->actingAs($user)->postJson(sprintf('/api/storage-options/%s/parts', $storageOption->id), [
                'part_id' => $part->id,
                'quantity' => 100,
            ]);

            $response->assertStatus(201)
                ->assertJsonPath('quantity', 100);

            $this->assertDatabaseHas('storage_option_parts', [
                'storage_option_id' => $storageOption->id,
                'part_id' => $part->id,
                'quantity' => 100,
            ]);
        });

        it('should update quantity if part already assigned', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create([
                'family_id' => $user->family_id,
            ]);
            $part = Part::factory()->create();
            StorageOptionPart::factory()->create([
                'storage_option_id' => $storageOption->id,
                'part_id' => $part->id,
                'quantity' => 50,
            ]);

            $response = $this->actingAs($user)->postJson(sprintf('/api/storage-options/%s/parts', $storageOption->id), [
                'part_id' => $part->id,
                'quantity' => 150,
            ]);

            $response->assertStatus(200)
                ->assertJsonPath('quantity', 150);

            expect(StorageOptionPart::query()->where('storage_option_id', $storageOption->id)
                ->where('part_id', $part->id)
                ->count())->toBe(1);
        });

        it('should return 404 for storage option from another family', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create();
            $part = Part::factory()->create();

            $response = $this->actingAs($user)->postJson(sprintf('/api/storage-options/%s/parts', $storageOption->id), [
                'part_id' => $part->id,
                'quantity' => 100,
            ]);

            $response->assertStatus(404);
        });

        it('should return 401 when unauthenticated', function (): void {
            $storageOption = StorageOption::factory()->create();
            $part = Part::factory()->create();

            $response = $this->postJson(sprintf('/api/storage-options/%s/parts', $storageOption->id), [
                'part_id' => $part->id,
                'quantity' => 100,
            ]);

            $response->assertStatus(401);
        });

        it('should require part_id and quantity', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create([
                'family_id' => $user->family_id,
            ]);

            $response = $this->actingAs($user)->postJson(sprintf('/api/storage-options/%s/parts', $storageOption->id), []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['part_id', 'quantity']);
        });
    });

    describe('removePart', function (): void {
        it('should remove a part from a storage option', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create([
                'family_id' => $user->family_id,
            ]);
            $part = Part::factory()->create();
            $storageOptionPart = StorageOptionPart::factory()->create([
                'storage_option_id' => $storageOption->id,
                'part_id' => $part->id,
            ]);

            $response = $this->actingAs($user)->deleteJson(sprintf('/api/storage-options/%s/parts/%s', $storageOption->id, $storageOptionPart->id));

            $response->assertStatus(204);
            $this->assertDatabaseMissing('storage_option_parts', ['id' => $storageOptionPart->id]);
        });

        it('should return 404 for storage option from another family', function (): void {
            $user = User::factory()->create();
            $storageOption = StorageOption::factory()->create();
            $part = Part::factory()->create();
            $storageOptionPart = StorageOptionPart::factory()->create([
                'storage_option_id' => $storageOption->id,
                'part_id' => $part->id,
            ]);

            $response = $this->actingAs($user)->deleteJson(sprintf('/api/storage-options/%s/parts/%s', $storageOption->id, $storageOptionPart->id));

            $response->assertStatus(404);
        });

        it('should return 401 when unauthenticated', function (): void {
            $storageOption = StorageOption::factory()->create();
            $part = Part::factory()->create();
            $storageOptionPart = StorageOptionPart::factory()->create([
                'storage_option_id' => $storageOption->id,
                'part_id' => $part->id,
            ]);

            $response = $this->deleteJson(sprintf('/api/storage-options/%s/parts/%s', $storageOption->id, $storageOptionPart->id));

            $response->assertStatus(401);
        });
    });
});

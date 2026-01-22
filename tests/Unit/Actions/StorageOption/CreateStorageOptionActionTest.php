<?php

declare(strict_types=1);

use App\Actions\StorageOption\CreateStorageOptionAction;
use App\DataTransferObjects\CreateStorageOptionData;
use App\Models\Family;
use App\Models\StorageOption;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('CreateStorageOptionAction', function (): void {
    it('should create a storage option', function (): void {
        // arrange
        $family = Family::factory()->create();
        $action = new CreateStorageOptionAction;
        $data = new CreateStorageOptionData(
            familyId: $family->id,
            name: 'Cabinet 1',
            description: 'Main storage cabinet',
            parentId: null,
            row: null,
            column: null,
        );

        // act
        $storageOption = $action->execute($data);

        // assert
        expect($storageOption)->toBeInstanceOf(StorageOption::class)
            ->and($storageOption->family_id)->toBe($family->id)
            ->and($storageOption->name)->toBe('Cabinet 1')
            ->and($storageOption->description)->toBe('Main storage cabinet');
    });

    it('should create a storage option with parent', function (): void {
        // arrange
        $family = Family::factory()->create();
        $parent = StorageOption::factory()->create(['family_id' => $family->id]);
        $action = new CreateStorageOptionAction;
        $data = new CreateStorageOptionData(
            familyId: $family->id,
            name: 'Drawer A1',
            description: null,
            parentId: $parent->id,
            row: 1,
            column: 2,
        );

        // act
        $storageOption = $action->execute($data);

        // assert
        expect($storageOption->parent_id)->toBe($parent->id)
            ->and($storageOption->row)->toBe(1)
            ->and($storageOption->column)->toBe(2);
    });

    it('should persist storage option to database', function (): void {
        // arrange
        $family = Family::factory()->create();
        $action = new CreateStorageOptionAction;
        $data = new CreateStorageOptionData(
            familyId: $family->id,
            name: 'Persisted Cabinet',
            description: null,
            parentId: null,
            row: null,
            column: null,
        );

        // act
        $action->execute($data);

        // assert
        expect(StorageOption::where('name', 'Persisted Cabinet')->exists())->toBeTrue();
    });
});

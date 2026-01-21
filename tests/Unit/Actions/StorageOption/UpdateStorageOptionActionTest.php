<?php

declare(strict_types=1);

use App\Actions\StorageOption\UpdateStorageOptionAction;
use App\DataTransferObjects\UpdateStorageOptionData;
use App\Models\StorageOption;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UpdateStorageOptionAction', function (): void {
    it('should update a storage option', function (): void {
        // arrange
        $storageOption = StorageOption::factory()->create([
            'name' => 'Old Name',
            'description' => 'Old description',
        ]);
        $action = new UpdateStorageOptionAction;
        $data = new UpdateStorageOptionData(
            name: 'New Name',
            description: 'New description',
        );

        // act
        $updated = $action->execute($storageOption, $data);

        // assert
        expect($updated->name)->toBe('New Name')
            ->and($updated->description)->toBe('New description');
    });

    it('should update row and column', function (): void {
        // arrange
        $storageOption = StorageOption::factory()->create();
        $action = new UpdateStorageOptionAction;
        $data = new UpdateStorageOptionData(
            name: 'Drawer',
            row: 3,
            column: 4,
        );

        // act
        $updated = $action->execute($storageOption, $data);

        // assert
        expect($updated->row)->toBe(3)
            ->and($updated->column)->toBe(4);
    });

    it('should persist changes to database', function (): void {
        // arrange
        $storageOption = StorageOption::factory()->create(['name' => 'Old Name']);
        $action = new UpdateStorageOptionAction;
        $data = new UpdateStorageOptionData(name: 'Updated Name');

        // act
        $action->execute($storageOption, $data);

        // assert
        expect(StorageOption::find($storageOption->id)->name)->toBe('Updated Name');
    });
});

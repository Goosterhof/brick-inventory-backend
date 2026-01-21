<?php

declare(strict_types=1);

use App\Actions\StorageOption\DeleteStorageOptionAction;
use App\Models\StorageOption;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DeleteStorageOptionAction', function (): void {
    it('should delete a storage option', function (): void {
        // arrange
        $storageOption = StorageOption::factory()->create();
        $action = new DeleteStorageOptionAction;

        // act
        $action->execute($storageOption);

        // assert
        expect(StorageOption::find($storageOption->id))->toBeNull();
    });

    it('should cascade delete children', function (): void {
        // arrange
        $parent = StorageOption::factory()->create();
        $child = StorageOption::factory()->create([
            'family_id' => $parent->family_id,
            'parent_id' => $parent->id,
        ]);
        $action = new DeleteStorageOptionAction;

        // act
        $action->execute($parent);

        // assert
        expect(StorageOption::find($parent->id))->toBeNull()
            ->and(StorageOption::find($child->id))->toBeNull();
    });
});

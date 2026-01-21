<?php

declare(strict_types=1);

use App\Actions\StorageOption\RemovePartFromStorageAction;
use App\Models\StorageOptionPart;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('RemovePartFromStorageAction', function (): void {
    it('should remove a part from storage option', function (): void {
        // arrange
        $storageOptionPart = StorageOptionPart::factory()->create();
        $action = new RemovePartFromStorageAction;

        // act
        $action->execute($storageOptionPart);

        // assert
        expect(StorageOptionPart::find($storageOptionPart->id))->toBeNull();
    });
});

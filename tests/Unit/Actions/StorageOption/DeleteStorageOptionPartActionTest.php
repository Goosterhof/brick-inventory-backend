<?php

declare(strict_types=1);

use App\Actions\StorageOption\DeleteStorageOptionPartAction;
use App\Models\StorageOptionPart;

describe('DeleteStorageOptionPartAction', function (): void {
    it('should call delete on the storage option part', function (): void {
        // arrange
        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('delete')->once();

        $action = new DeleteStorageOptionPartAction;

        // act
        $action->execute($storageOptionPart);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });
});

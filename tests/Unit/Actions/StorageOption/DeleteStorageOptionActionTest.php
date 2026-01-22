<?php

declare(strict_types=1);

use App\Actions\StorageOption\DeleteStorageOptionAction;
use App\Models\StorageOption;

describe('DeleteStorageOptionAction', function (): void {
    it('should call delete on the storage option', function (): void {
        // arrange
        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('delete')->once();

        $action = new DeleteStorageOptionAction;

        // act
        $action->execute($storageOption);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });
});

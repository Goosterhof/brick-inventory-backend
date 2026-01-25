<?php

declare(strict_types=1);

use App\Actions\StorageOption\GetStorageOptionAction;
use App\Models\StorageOption;

describe('GetStorageOptionAction', function (): void {
    it('should load the children relationship', function (): void {
        // arrange
        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('load')
            ->with('children')
            ->once();

        $action = new GetStorageOptionAction;

        // act
        $action->execute($storageOption);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });

    it('should return the same storage option instance', function (): void {
        // arrange
        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('load')->with('children');

        $action = new GetStorageOptionAction;

        // act
        $result = $action->execute($storageOption);

        // assert
        expect($result)->toBe($storageOption);
    });
});

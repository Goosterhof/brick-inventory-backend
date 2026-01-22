<?php

declare(strict_types=1);

use App\Actions\StorageOption\UpdateStorageOptionAction;
use App\DataTransferObjects\UpdateStorageOptionData;
use App\Models\StorageOption;

describe('UpdateStorageOptionAction', function (): void {
    it('should update storage option properties', function (): void {
        // arrange
        $storageOption = Mockery::mock(StorageOption::class)->makePartial();
        $storageOption->shouldReceive('save')->once();

        $action = new UpdateStorageOptionAction;
        $data = new UpdateStorageOptionData(
            name: 'New Name',
            description: 'New description',
            parentId: null,
            row: null,
            column: null,
        );

        // act
        $result = $action->execute($storageOption, $data);

        // assert
        expect($result)->toBe($storageOption)
            ->and($storageOption->name)->toBe('New Name')
            ->and($storageOption->description)->toBe('New description');
    });

    it('should update row and column', function (): void {
        // arrange
        $storageOption = Mockery::mock(StorageOption::class)->makePartial();
        $storageOption->shouldReceive('save')->once();

        $action = new UpdateStorageOptionAction;
        $data = new UpdateStorageOptionData(
            name: 'Drawer',
            description: null,
            parentId: null,
            row: 3,
            column: 4,
        );

        // act
        $action->execute($storageOption, $data);

        // assert
        expect($storageOption->row)->toBe(3)
            ->and($storageOption->column)->toBe(4);
    });

    it('should update parent_id', function (): void {
        // arrange
        $storageOption = Mockery::mock(StorageOption::class)->makePartial();
        $storageOption->shouldReceive('save')->once();

        $action = new UpdateStorageOptionAction;
        $data = new UpdateStorageOptionData(
            name: 'Drawer',
            description: null,
            parentId: 5,
            row: null,
            column: null,
        );

        // act
        $action->execute($storageOption, $data);

        // assert
        expect($storageOption->parent_id)->toBe(5);
    });

    it('should call save on the storage option', function (): void {
        // arrange
        $storageOption = Mockery::mock(StorageOption::class)->makePartial();
        $storageOption->shouldReceive('save')->once();

        $action = new UpdateStorageOptionAction;
        $data = new UpdateStorageOptionData(
            name: 'Updated',
            description: null,
            parentId: null,
            row: null,
            column: null,
        );

        // act
        $action->execute($storageOption, $data);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });
});

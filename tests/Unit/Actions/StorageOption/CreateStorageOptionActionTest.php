<?php

declare(strict_types=1);

use App\Actions\StorageOption\CreateStorageOptionAction;
use App\DataTransferObjects\CreateStorageOptionData;
use App\Models\StorageOption;

describe('CreateStorageOptionAction', function (): void {
    it('should create a storage option with the provided data', function (): void {
        // arrange
        $storageOptionInstance = Mockery::mock(StorageOption::class)->makePartial();
        $storageOptionInstance->shouldReceive('save')->once();

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newInstance')
            ->withNoArgs()
            ->once()
            ->andReturn($storageOptionInstance);

        $action = new CreateStorageOptionAction($storageOption);
        $data = new CreateStorageOptionData(
            familyId: 1,
            name: 'Cabinet 1',
            description: 'Main storage cabinet',
            parentId: null,
            row: null,
            column: null,
        );

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($storageOptionInstance)
            ->and($storageOptionInstance->family_id)->toBe(1)
            ->and($storageOptionInstance->name)->toBe('Cabinet 1')
            ->and($storageOptionInstance->description)->toBe('Main storage cabinet');
    });

    it('should set parent_id, row, and column when provided', function (): void {
        // arrange
        $storageOptionInstance = Mockery::mock(StorageOption::class)->makePartial();
        $storageOptionInstance->shouldReceive('save')->once();

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newInstance')
            ->withNoArgs()
            ->once()
            ->andReturn($storageOptionInstance);

        $action = new CreateStorageOptionAction($storageOption);
        $data = new CreateStorageOptionData(
            familyId: 1,
            name: 'Drawer A1',
            description: null,
            parentId: 5,
            row: 1,
            column: 2,
        );

        // act
        $action->execute($data);

        // assert
        expect($storageOptionInstance->parent_id)->toBe(5)
            ->and($storageOptionInstance->row)->toBe(1)
            ->and($storageOptionInstance->column)->toBe(2);
    });

    it('should call save on the storage option', function (): void {
        // arrange
        $storageOptionInstance = Mockery::mock(StorageOption::class)->makePartial();
        $storageOptionInstance->shouldReceive('save')->once();

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newInstance')
            ->withNoArgs()
            ->andReturn($storageOptionInstance);

        $action = new CreateStorageOptionAction($storageOption);
        $data = new CreateStorageOptionData(
            familyId: 1,
            name: 'Test Cabinet',
            description: null,
            parentId: null,
            row: null,
            column: null,
        );

        // act
        $action->execute($data);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });
});

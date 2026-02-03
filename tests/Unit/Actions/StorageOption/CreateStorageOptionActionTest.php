<?php

declare(strict_types=1);

use App\Actions\StorageOption\CreateStorageOptionAction;
use App\Contracts\StorageOption\StorageOptionDataInterface;
use App\Models\StorageOption;
use App\Models\User;

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

        $user = Mockery::mock(User::class)->makePartial();
        $user->family_id = 1;

        $action = new CreateStorageOptionAction($storageOption, $user);
        $data = new class implements StorageOptionDataInterface
        {
            public string $name = 'Cabinet 1';

            public ?string $description = 'Main storage cabinet';

            public ?int $parentId = null;

            public ?int $row = null;

            public ?int $column = null;
        };

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

        $user = Mockery::mock(User::class)->makePartial();
        $user->family_id = 1;

        $action = new CreateStorageOptionAction($storageOption, $user);
        $data = new class implements StorageOptionDataInterface
        {
            public string $name = 'Drawer A1';

            public ?string $description = null;

            public ?int $parentId = 5;

            public ?int $row = 1;

            public ?int $column = 2;
        };

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

        $user = Mockery::mock(User::class)->makePartial();
        $user->family_id = 1;

        $action = new CreateStorageOptionAction($storageOption, $user);
        $data = new class implements StorageOptionDataInterface
        {
            public string $name = 'Test Cabinet';

            public ?string $description = null;

            public ?int $parentId = null;

            public ?int $row = null;

            public ?int $column = null;
        };

        // act
        $action->execute($data);

        // assert - Mockery expectations verify save() was called
    });
});

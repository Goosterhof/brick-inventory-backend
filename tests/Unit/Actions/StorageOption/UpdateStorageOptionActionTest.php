<?php

declare(strict_types=1);

use App\Actions\StorageOption\UpdateStorageOptionAction;
use App\Contracts\StorageOption\UpdateStorageOptionInterface;
use App\Models\StorageOption;

describe('UpdateStorageOptionAction', function (): void {
    it('should update storage option properties', function (): void {
        // arrange
        $storageOption = Mockery::mock(StorageOption::class)->makePartial();
        $storageOption->shouldReceive('save')->once();

        $action = new UpdateStorageOptionAction;
        $data = new class implements UpdateStorageOptionInterface
        {
            public string $name = 'New Name';

            public ?string $description = 'New description';

            public ?int $parentId = null;

            public ?int $row = null;

            public ?int $column = null;
        };

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
        $data = new class implements UpdateStorageOptionInterface
        {
            public string $name = 'Drawer';

            public ?string $description = null;

            public ?int $parentId = null;

            public ?int $row = 3;

            public ?int $column = 4;
        };

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
        $data = new class implements UpdateStorageOptionInterface
        {
            public string $name = 'Drawer';

            public ?string $description = null;

            public ?int $parentId = 5;

            public ?int $row = null;

            public ?int $column = null;
        };

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
        $data = new class implements UpdateStorageOptionInterface
        {
            public string $name = 'Updated';

            public ?string $description = null;

            public ?int $parentId = null;

            public ?int $row = null;

            public ?int $column = null;
        };

        // act
        $action->execute($storageOption, $data);

        // assert - Mockery expectations verify the interactions
    });
});

<?php

declare(strict_types=1);

use App\Actions\StorageOption\UpdateStorageOptionAction;
use App\DataTransferObjects\StorageOption\StorageOptionData;
use App\Models\StorageOption;
use Illuminate\Database\ConnectionInterface;

covers(UpdateStorageOptionAction::class);

describe('UpdateStorageOptionAction', function (): void {
    beforeEach(function (): void {
        $this->db = Mockery::mock(ConnectionInterface::class);
        $this->db->allows('transaction')->andReturnUsing(fn (Closure $callback) => $callback());
    });

    it('should update storage option properties', function (): void {
        // arrange
        $savedValues = [];
        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$savedValues): void {
            $savedValues[$key] = $value;
        });
        $storageOption->allows('getAttribute')->andReturnUsing(function ($key) use (&$savedValues): mixed {
            return $savedValues[$key] ?? null;
        });
        $storageOption->shouldReceive('save')->once();

        $action = new UpdateStorageOptionAction($this->db);
        $data = new StorageOptionData(
            name: 'New Name',
            description: 'New description',
        );

        // act
        $result = $action->execute($storageOption, $data);

        // assert
        expect($result)->toBe($storageOption)
            ->and($savedValues['name'])->toBe('New Name')
            ->and($savedValues['description'])->toBe('New description');
    });

    it('should update row and column', function (): void {
        // arrange
        $savedValues = [];
        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$savedValues): void {
            $savedValues[$key] = $value;
        });
        $storageOption->allows('getAttribute')->andReturnUsing(function ($key) use (&$savedValues): mixed {
            return $savedValues[$key] ?? null;
        });
        $storageOption->shouldReceive('save')->once();

        $action = new UpdateStorageOptionAction($this->db);
        $data = new StorageOptionData(
            name: 'Drawer',
            row: 3,
            column: 4,
        );

        // act
        $action->execute($storageOption, $data);

        // assert
        expect($savedValues['row'])->toBe(3)
            ->and($savedValues['column'])->toBe(4);
    });

    it('should update parent_id', function (): void {
        // arrange
        $savedValues = [];
        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$savedValues): void {
            $savedValues[$key] = $value;
        });
        $storageOption->allows('getAttribute')->andReturnUsing(function ($key) use (&$savedValues): mixed {
            return $savedValues[$key] ?? null;
        });
        $storageOption->shouldReceive('save')->once();

        $action = new UpdateStorageOptionAction($this->db);
        $data = new StorageOptionData(
            name: 'Drawer',
            parentId: 5,
        );

        // act
        $action->execute($storageOption, $data);

        // assert
        expect($savedValues['parent_id'])->toBe(5);
    });

    it('should call save on the storage option', function (): void {
        // arrange
        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->allows('setAttribute');
        $storageOption->allows('getAttribute');
        $storageOption->shouldReceive('save')->once();

        $action = new UpdateStorageOptionAction($this->db);
        $data = new StorageOptionData(
            name: 'Updated',
        );

        // act
        $action->execute($storageOption, $data);

        // assert - Mockery expectations verify the interactions
    });
});

<?php

declare(strict_types = 1);

use App\Actions\StorageOption\CreateStorageOptionAction;
use App\DataTransferObjects\StorageOption\StorageOptionData;
use App\Models\Family;
use App\Models\StorageOption;
use Illuminate\Database\ConnectionInterface;

covers(CreateStorageOptionAction::class);

describe('CreateStorageOptionAction', function(): void {
    beforeEach(function(): void {
        $this->db = \Mockery::mock(ConnectionInterface::class);
        $this->db->allows('transaction')->andReturnUsing(fn(\Closure $callback) => $callback());
    });

    it('should create a storage option with the provided data', function(): void {
        // arrange
        $savedValues = [];
        $storageOptionInstance = \Mockery::mock(StorageOption::class);
        $storageOptionInstance->allows('setAttribute')->andReturnUsing(function($key, $value) use (&$savedValues): void {
            $savedValues[$key] = $value;
        });
        $storageOptionInstance->allows('getAttribute')->andReturnUsing(function($key) use (&$savedValues): mixed {
            return $savedValues[$key] ?? null;
        });
        $storageOptionInstance->shouldReceive('save')->once();

        $storageOption = \Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newInstance')
            ->withNoArgs()
            ->once()
            ->andReturn($storageOptionInstance);

        $family = \Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(1);

        $action = new CreateStorageOptionAction($storageOption, $this->db);
        $data = new StorageOptionData(
            name: 'Cabinet 1',
            description: 'Main storage cabinet',
        );

        // act
        $result = $action->execute($family, $data);

        // assert
        expect($result)->toBe($storageOptionInstance)
            ->and($savedValues['family_id'])->toBe(1)
            ->and($savedValues['name'])->toBe('Cabinet 1')
            ->and($savedValues['description'])->toBe('Main storage cabinet');
    });

    it('should set parent_id, row, and column when provided', function(): void {
        // arrange
        $savedValues = [];
        $storageOptionInstance = \Mockery::mock(StorageOption::class);
        $storageOptionInstance->allows('setAttribute')->andReturnUsing(function($key, $value) use (&$savedValues): void {
            $savedValues[$key] = $value;
        });
        $storageOptionInstance->allows('getAttribute')->andReturnUsing(function($key) use (&$savedValues): mixed {
            return $savedValues[$key] ?? null;
        });
        $storageOptionInstance->shouldReceive('save')->once();

        $storageOption = \Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newInstance')
            ->withNoArgs()
            ->once()
            ->andReturn($storageOptionInstance);

        $family = \Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(1);

        $action = new CreateStorageOptionAction($storageOption, $this->db);
        $data = new StorageOptionData(
            name: 'Drawer A1',
            parentId: 5,
            row: 1,
            column: 2,
        );

        // act
        $action->execute($family, $data);

        // assert
        expect($savedValues['parent_id'])->toBe(5)
            ->and($savedValues['row'])->toBe(1)
            ->and($savedValues['column'])->toBe(2);
    });

    it('should call save on the storage option', function(): void {
        // arrange
        $storageOptionInstance = \Mockery::mock(StorageOption::class);
        $storageOptionInstance->allows('setAttribute');
        $storageOptionInstance->allows('getAttribute');
        $storageOptionInstance->shouldReceive('save')->once();

        $storageOption = \Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newInstance')
            ->withNoArgs()
            ->andReturn($storageOptionInstance);

        $family = \Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(1);

        $action = new CreateStorageOptionAction($storageOption, $this->db);
        $data = new StorageOptionData(
            name: 'Test Cabinet',
        );

        // act
        $action->execute($family, $data);

        // assert - Mockery expectations verify save() was called
    });
});

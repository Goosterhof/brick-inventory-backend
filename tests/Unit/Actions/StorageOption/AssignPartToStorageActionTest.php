<?php

declare(strict_types=1);

use App\Actions\StorageOption\AssignPartToStorageAction;
use App\DataTransferObjects\StorageOption\AssignPartToStorageData;
use App\Models\StorageOption;
use App\Models\StorageOptionPart;
use Illuminate\Database\Eloquent\Builder;

describe('AssignPartToStorageAction', function (): void {
    it('should create a new assignment when one does not exist', function (): void {
        // arrange
        $savedValues = [];
        $storageOptionPartInstance = Mockery::mock(StorageOptionPart::class);
        $storageOptionPartInstance->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$savedValues): void {
            $savedValues[$key] = $value;
        });
        $storageOptionPartInstance->allows('getAttribute')->andReturnUsing(function ($key) use (&$savedValues): mixed {
            return $savedValues[$key] ?? null;
        });
        $storageOptionPartInstance->shouldReceive('save')->once();

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('storage_option_id', 1)->once()->andReturnSelf();
        $builder->shouldReceive('where')->with('part_id', 2)->once()->andReturnSelf();
        $builder->shouldReceive('where')->with('color_id', null)->once()->andReturnSelf();
        $builder->shouldReceive('first')->once()->andReturn(null);

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')->withNoArgs()->once()->andReturn($builder);
        $storageOptionPart->shouldReceive('newInstance')->withNoArgs()->once()->andReturn($storageOptionPartInstance);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->allows('getAttribute')->with('id')->andReturn(1);

        $action = new AssignPartToStorageAction($storageOptionPart);
        $data = new AssignPartToStorageData(
            partId: 2,
            colorId: null,
            quantity: 50,
        );

        // act
        $result = $action->execute($storageOption, $data);

        // assert
        expect($result)->toBe($storageOptionPartInstance)
            ->and($savedValues['storage_option_id'])->toBe(1)
            ->and($savedValues['part_id'])->toBe(2)
            ->and($savedValues['color_id'])->toBeNull()
            ->and($savedValues['quantity'])->toBe(50);
    });

    it('should update existing assignment when one exists', function (): void {
        // arrange
        $existingSavedValues = [];
        $existingInstance = Mockery::mock(StorageOptionPart::class);
        $existingInstance->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$existingSavedValues): void {
            $existingSavedValues[$key] = $value;
        });
        $existingInstance->allows('getAttribute')->andReturnUsing(function ($key) use (&$existingSavedValues): mixed {
            return $existingSavedValues[$key] ?? null;
        });
        $existingInstance->shouldReceive('save')->once();

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('storage_option_id', 1)->once()->andReturnSelf();
        $builder->shouldReceive('where')->with('part_id', 2)->once()->andReturnSelf();
        $builder->shouldReceive('where')->with('color_id', 3)->once()->andReturnSelf();
        $builder->shouldReceive('first')->once()->andReturn($existingInstance);

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')->withNoArgs()->once()->andReturn($builder);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->allows('getAttribute')->with('id')->andReturn(1);

        $action = new AssignPartToStorageAction($storageOptionPart);
        $data = new AssignPartToStorageData(
            partId: 2,
            colorId: 3,
            quantity: 100,
        );

        // act
        $result = $action->execute($storageOption, $data);

        // assert
        expect($result)->toBe($existingInstance)
            ->and($existingSavedValues['quantity'])->toBe(100);
    });

    it('should call save on the storage option part', function (): void {
        // arrange
        $storageOptionPartInstance = Mockery::mock(StorageOptionPart::class);
        $storageOptionPartInstance->allows('setAttribute');
        $storageOptionPartInstance->allows('getAttribute');
        $storageOptionPartInstance->shouldReceive('save')->once();

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->andReturnSelf();
        $builder->shouldReceive('first')->andReturn(null);

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')->andReturn($builder);
        $storageOptionPart->shouldReceive('newInstance')->andReturn($storageOptionPartInstance);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->allows('getAttribute')->with('id')->andReturn(1);

        $action = new AssignPartToStorageAction($storageOptionPart);
        $data = new AssignPartToStorageData(
            partId: 2,
            colorId: null,
            quantity: 100,
        );

        // act
        $action->execute($storageOption, $data);

        // assert - Mockery expectations verify the interactions
    });
});

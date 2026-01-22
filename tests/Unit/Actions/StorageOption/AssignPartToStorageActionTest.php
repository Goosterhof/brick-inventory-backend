<?php

declare(strict_types=1);

use App\Actions\StorageOption\AssignPartToStorageAction;
use App\DataTransferObjects\AssignPartToStorageData;
use App\Models\StorageOptionPart;
use Illuminate\Database\Eloquent\Builder;

describe('AssignPartToStorageAction', function (): void {
    it('should create a new assignment when one does not exist', function (): void {
        // arrange
        $storageOptionPartInstance = Mockery::mock(StorageOptionPart::class)->makePartial();
        $storageOptionPartInstance->shouldReceive('save')->once();

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('storage_option_id', 1)->once()->andReturnSelf();
        $builder->shouldReceive('where')->with('part_id', 2)->once()->andReturnSelf();
        $builder->shouldReceive('where')->with('color_id', null)->once()->andReturnSelf();
        $builder->shouldReceive('first')->once()->andReturn(null);

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')->withNoArgs()->once()->andReturn($builder);
        $storageOptionPart->shouldReceive('newInstance')->withNoArgs()->once()->andReturn($storageOptionPartInstance);

        $action = new AssignPartToStorageAction($storageOptionPart);
        $data = new AssignPartToStorageData(
            storageOptionId: 1,
            partId: 2,
            colorId: null,
            quantity: 50,
        );

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($storageOptionPartInstance)
            ->and($storageOptionPartInstance->storage_option_id)->toBe(1)
            ->and($storageOptionPartInstance->part_id)->toBe(2)
            ->and($storageOptionPartInstance->color_id)->toBeNull()
            ->and($storageOptionPartInstance->quantity)->toBe(50);
    });

    it('should update existing assignment when one exists', function (): void {
        // arrange
        $existingInstance = Mockery::mock(StorageOptionPart::class)->makePartial();
        $existingInstance->shouldReceive('save')->once();

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('storage_option_id', 1)->once()->andReturnSelf();
        $builder->shouldReceive('where')->with('part_id', 2)->once()->andReturnSelf();
        $builder->shouldReceive('where')->with('color_id', 3)->once()->andReturnSelf();
        $builder->shouldReceive('first')->once()->andReturn($existingInstance);

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')->withNoArgs()->once()->andReturn($builder);

        $action = new AssignPartToStorageAction($storageOptionPart);
        $data = new AssignPartToStorageData(
            storageOptionId: 1,
            partId: 2,
            colorId: 3,
            quantity: 100,
        );

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($existingInstance)
            ->and($existingInstance->quantity)->toBe(100);
    });

    it('should call save on the storage option part', function (): void {
        // arrange
        $storageOptionPartInstance = Mockery::mock(StorageOptionPart::class)->makePartial();
        $storageOptionPartInstance->shouldReceive('save')->once();

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->andReturnSelf();
        $builder->shouldReceive('first')->andReturn(null);

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')->andReturn($builder);
        $storageOptionPart->shouldReceive('newInstance')->andReturn($storageOptionPartInstance);

        $action = new AssignPartToStorageAction($storageOptionPart);
        $data = new AssignPartToStorageData(
            storageOptionId: 1,
            partId: 2,
            colorId: null,
            quantity: 100,
        );

        // act
        $action->execute($data);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });
});

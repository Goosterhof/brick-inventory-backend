<?php

declare(strict_types=1);

use App\Actions\StorageOption\AssignPartToStorageAction;
use App\DataTransferObjects\AssignPartToStorageData;
use App\Models\StorageOptionPart;
use Illuminate\Database\Eloquent\Builder;

describe('AssignPartToStorageAction', function (): void {
    it('should assign a part to a storage option', function (): void {
        // arrange
        $storageOptionPartInstance = Mockery::mock(StorageOptionPart::class)->makePartial();
        $storageOptionPartInstance->shouldReceive('save')->once();

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('firstOrNew')
            ->with([
                'storage_option_id' => 1,
                'part_id' => 2,
                'color_id' => null,
            ])
            ->once()
            ->andReturn($storageOptionPartInstance);

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')
            ->withNoArgs()
            ->once()
            ->andReturn($builder);

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
            ->and($storageOptionPartInstance->quantity)->toBe(50);
    });

    it('should assign a part with color', function (): void {
        // arrange
        $storageOptionPartInstance = Mockery::mock(StorageOptionPart::class)->makePartial();
        $storageOptionPartInstance->shouldReceive('save')->once();

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('firstOrNew')
            ->with([
                'storage_option_id' => 1,
                'part_id' => 2,
                'color_id' => 3,
            ])
            ->once()
            ->andReturn($storageOptionPartInstance);

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')
            ->withNoArgs()
            ->once()
            ->andReturn($builder);

        $action = new AssignPartToStorageAction($storageOptionPart);
        $data = new AssignPartToStorageData(
            storageOptionId: 1,
            partId: 2,
            colorId: 3,
            quantity: 25,
        );

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($storageOptionPartInstance)
            ->and($storageOptionPartInstance->quantity)->toBe(25);
    });

    it('should call save on the storage option part', function (): void {
        // arrange
        $storageOptionPartInstance = Mockery::mock(StorageOptionPart::class)->makePartial();
        $storageOptionPartInstance->shouldReceive('save')->once();

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('firstOrNew')->andReturn($storageOptionPartInstance);

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')->andReturn($builder);

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

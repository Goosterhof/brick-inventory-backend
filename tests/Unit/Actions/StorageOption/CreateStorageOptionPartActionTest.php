<?php

declare(strict_types=1);

use App\Actions\StorageOption\CreateStorageOptionPartAction;
use App\Contracts\StorageOption\AssignPartToStorageInterface;
use App\Models\StorageOption;
use App\Models\StorageOptionPart;
use Illuminate\Database\Eloquent\Builder;

describe('CreateStorageOptionPartAction', function (): void {
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

        $storageOption = Mockery::mock(StorageOption::class)->makePartial();
        $storageOption->id = 1;

        $action = new CreateStorageOptionPartAction($storageOptionPart);
        $data = new class implements AssignPartToStorageInterface
        {
            public int $partId = 2;

            public ?int $colorId = null;

            public int $quantity = 50;
        };

        // act
        $result = $action->execute($storageOption, $data);

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

        $storageOption = Mockery::mock(StorageOption::class)->makePartial();
        $storageOption->id = 1;

        $action = new CreateStorageOptionPartAction($storageOptionPart);
        $data = new class implements AssignPartToStorageInterface
        {
            public int $partId = 2;

            public ?int $colorId = 3;

            public int $quantity = 100;
        };

        // act
        $result = $action->execute($storageOption, $data);

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

        $storageOption = Mockery::mock(StorageOption::class)->makePartial();
        $storageOption->id = 1;

        $action = new CreateStorageOptionPartAction($storageOptionPart);
        $data = new class implements AssignPartToStorageInterface
        {
            public int $partId = 2;

            public ?int $colorId = null;

            public int $quantity = 100;
        };

        // act
        $action->execute($storageOption, $data);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });
});

<?php

declare(strict_types=1);

use App\Actions\StorageOption\GetStorageOptionPartsAction;
use App\Models\StorageOption;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

describe('GetStorageOptionPartsAction', function (): void {
    it('should query storage option parts with relationships', function (): void {
        // arrange
        $collection = new Collection;

        $relation = Mockery::mock(HasMany::class);
        $relation->shouldReceive('with')
            ->with(['part', 'color'])
            ->once()
            ->andReturnSelf();
        $relation->shouldReceive('get')
            ->once()
            ->andReturn($collection);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('storageOptionParts')
            ->once()
            ->andReturn($relation);

        $action = new GetStorageOptionPartsAction;

        // act
        $result = $action->execute($storageOption);

        // assert
        expect($result)->toBe($collection);
    });

    it('should load part relationship', function (): void {
        // arrange
        $collection = new Collection;

        $relation = Mockery::mock(HasMany::class);
        $relation->shouldReceive('with')
            ->withArgs(fn (array $relations): bool => in_array('part', $relations, true))
            ->once()
            ->andReturnSelf();
        $relation->shouldReceive('get')->andReturn($collection);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('storageOptionParts')->andReturn($relation);

        $action = new GetStorageOptionPartsAction;

        // act
        $action->execute($storageOption);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });

    it('should load color relationship', function (): void {
        // arrange
        $collection = new Collection;

        $relation = Mockery::mock(HasMany::class);
        $relation->shouldReceive('with')
            ->withArgs(fn (array $relations): bool => in_array('color', $relations, true))
            ->once()
            ->andReturnSelf();
        $relation->shouldReceive('get')->andReturn($collection);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('storageOptionParts')->andReturn($relation);

        $action = new GetStorageOptionPartsAction;

        // act
        $action->execute($storageOption);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });
});

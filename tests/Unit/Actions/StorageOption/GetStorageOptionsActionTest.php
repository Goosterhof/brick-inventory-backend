<?php

declare(strict_types=1);

use App\Actions\StorageOption\GetStorageOptionsAction;
use App\Models\StorageOption;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

covers(GetStorageOptionsAction::class);

describe('GetStorageOptionsAction', function (): void {
    it('should query storage options by user family_id', function (): void {
        // arrange
        $user = Mockery::mock(User::class);
        $user->allows('getAttribute')->with('family_id')->andReturn(5);

        $collection = new Collection;

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')
            ->with('family_id', 5)
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('whereNull')->with('parent_id')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn($collection);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newQuery')
            ->once()
            ->andReturn($builder);

        $action = new GetStorageOptionsAction($storageOption);

        // act
        $result = $action->execute($user);

        // assert
        expect($result)->toBe($collection);
    });

    it('should filter to only root storage options', function (): void {
        // arrange
        $user = Mockery::mock(User::class);
        $user->allows('getAttribute')->with('family_id')->andReturn(1);

        $collection = new Collection;

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->andReturnSelf();
        $builder->shouldReceive('whereNull')
            ->with('parent_id')
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('get')->andReturn($collection);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newQuery')->andReturn($builder);

        $action = new GetStorageOptionsAction($storageOption);

        // act
        $action->execute($user);

        // assert - Mockery expectations verify the interactions
    });
});

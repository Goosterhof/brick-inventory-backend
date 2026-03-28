<?php

declare(strict_types=1);

use App\Actions\StorageOption\GetStorageOptionsAction;
use App\Models\StorageOption;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\CursorPaginator;

covers(GetStorageOptionsAction::class);

describe('GetStorageOptionsAction', function (): void {
    it('should query storage options by user family_id with cursor pagination', function (): void {
        // arrange
        $user = Mockery::mock(User::class);
        $user->allows('getAttribute')->with('family_id')->andReturn(5);

        $cursorPaginator = new CursorPaginator(collect(), 25);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')
            ->with('family_id', 5)
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('whereNull')
            ->with('parent_id')
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('orderBy')
            ->with('id')
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('cursorPaginate')
            ->once()
            ->andReturn($cursorPaginator);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newQuery')
            ->once()
            ->andReturn($builder);

        $action = new GetStorageOptionsAction($storageOption);

        // act
        $result = $action->execute($user);

        // assert
        expect($result)->toBe($cursorPaginator);
    });

    it('should cap per_page at 100', function (): void {
        // arrange
        $user = Mockery::mock(User::class);
        $user->allows('getAttribute')->with('family_id')->andReturn(1);

        $cursorPaginator = new CursorPaginator(collect(), 100);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->andReturnSelf();
        $builder->shouldReceive('whereNull')->andReturnSelf();
        $builder->shouldReceive('orderBy')->andReturnSelf();
        $builder->shouldReceive('cursorPaginate')
            ->withArgs(fn (int $perPage): bool => $perPage === 100)
            ->once()
            ->andReturn($cursorPaginator);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newQuery')->andReturn($builder);

        $action = new GetStorageOptionsAction($storageOption);

        // act
        $result = $action->execute($user, perPage: 200);

        // assert
        expect($result)->toBe($cursorPaginator);
    });
});

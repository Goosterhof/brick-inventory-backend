<?php

declare(strict_types=1);

use App\Actions\StorageOption\GetStorageOptionPartsAction;
use App\Models\StorageOption;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\CursorPaginator;

covers(GetStorageOptionPartsAction::class);

describe('GetStorageOptionPartsAction', function (): void {
    it('should query storage option parts with cursor pagination', function (): void {
        // arrange
        $cursorPaginator = new CursorPaginator(collect(), 25);

        $relation = Mockery::mock(HasMany::class);
        $relation->shouldReceive('orderBy')
            ->with('id')
            ->once()
            ->andReturnSelf();
        $relation->shouldReceive('cursorPaginate')
            ->once()
            ->andReturn($cursorPaginator);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('storageOptionParts')
            ->once()
            ->andReturn($relation);

        $action = new GetStorageOptionPartsAction;

        // act
        $result = $action->execute($storageOption);

        // assert
        expect($result)->toBe($cursorPaginator);
    });

    it('should cap per_page at 100', function (): void {
        // arrange
        $cursorPaginator = new CursorPaginator(collect(), 100);

        $relation = Mockery::mock(HasMany::class);
        $relation->shouldReceive('orderBy')->andReturnSelf();
        $relation->shouldReceive('cursorPaginate')
            ->withArgs(fn (int $perPage): bool => $perPage === 100)
            ->once()
            ->andReturn($cursorPaginator);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('storageOptionParts')
            ->andReturn($relation);

        $action = new GetStorageOptionPartsAction;

        // act
        $result = $action->execute($storageOption, perPage: 200);

        // assert
        expect($result)->toBe($cursorPaginator);
    });

    it('should use default per_page of 25', function (): void {
        // arrange
        $cursorPaginator = new CursorPaginator(collect(), 25);

        $relation = Mockery::mock(HasMany::class);
        $relation->shouldReceive('orderBy')->andReturnSelf();
        $relation->shouldReceive('cursorPaginate')
            ->withArgs(fn (int $perPage): bool => $perPage === 25)
            ->once()
            ->andReturn($cursorPaginator);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('storageOptionParts')
            ->andReturn($relation);

        $action = new GetStorageOptionPartsAction;

        // act
        $action->execute($storageOption);
    });
});

<?php

declare(strict_types=1);

use App\Actions\FamilySet\GetFamilySetsAction;
use App\Models\FamilySet;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\CursorPaginator;

covers(GetFamilySetsAction::class);

describe('GetFamilySetsAction', function (): void {
    it('should query family sets by user family_id with cursor pagination', function (): void {
        // arrange
        $user = Mockery::mock(User::class);
        $user->allows('getAttribute')->with('family_id')->andReturn(5);

        $cursorPaginator = new CursorPaginator(collect(), 25);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')
            ->with('family_id', 5)
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('orderByDesc')
            ->with('id')
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('cursorPaginate')
            ->once()
            ->andReturn($cursorPaginator);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('newQuery')
            ->once()
            ->andReturn($builder);

        $action = new GetFamilySetsAction($familySet);

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
        $builder->shouldReceive('orderByDesc')->andReturnSelf();
        $builder->shouldReceive('cursorPaginate')
            ->withArgs(fn (int $perPage): bool => $perPage === 100)
            ->once()
            ->andReturn($cursorPaginator);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('newQuery')->andReturn($builder);

        $action = new GetFamilySetsAction($familySet);

        // act
        $result = $action->execute($user, perPage: 200);

        // assert
        expect($result)->toBe($cursorPaginator);
    });

    it('should use default per_page of 25', function (): void {
        // arrange
        $user = Mockery::mock(User::class);
        $user->allows('getAttribute')->with('family_id')->andReturn(1);

        $cursorPaginator = new CursorPaginator(collect(), 25);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->andReturnSelf();
        $builder->shouldReceive('orderByDesc')->andReturnSelf();
        $builder->shouldReceive('cursorPaginate')
            ->withArgs(fn (int $perPage): bool => $perPage === 25)
            ->once()
            ->andReturn($cursorPaginator);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('newQuery')->andReturn($builder);

        $action = new GetFamilySetsAction($familySet);

        // act
        $action->execute($user);
    });
});

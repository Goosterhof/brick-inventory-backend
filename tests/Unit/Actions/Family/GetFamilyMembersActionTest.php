<?php

declare(strict_types=1);

use App\Actions\Family\GetFamilyMembersAction;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

describe('GetFamilyMembersAction', function (): void {
    it('should return users belonging to the family', function (): void {
        // arrange
        $family = Mockery::mock(Family::class);
        $family->shouldReceive('getAttribute')->with('id')->andReturn(3);

        $user1 = Mockery::mock(User::class);
        $user2 = Mockery::mock(User::class);

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')
            ->with('family_id', 3)
            ->andReturnSelf();
        $queryBuilder->shouldReceive('get')
            ->andReturn(new Collection([$user1, $user2]));

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newQuery')->once()->andReturn($queryBuilder);

        $action = new GetFamilyMembersAction($user);

        // act
        $result = $action->execute($family);

        // assert
        expect($result)->toHaveCount(2);
    });

    it('should return empty collection when family has no users', function (): void {
        // arrange
        $family = Mockery::mock(Family::class);
        $family->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->andReturnSelf();
        $queryBuilder->shouldReceive('get')->andReturn(new Collection([]));

        $user = Mockery::mock(User::class);
        $user->shouldReceive('newQuery')->once()->andReturn($queryBuilder);

        $action = new GetFamilyMembersAction($user);

        // act
        $result = $action->execute($family);

        // assert
        expect($result)->toHaveCount(0);
    });
});

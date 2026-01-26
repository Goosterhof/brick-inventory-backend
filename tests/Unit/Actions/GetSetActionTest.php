<?php

declare(strict_types=1);

use App\Actions\GetSetAction;
use App\Contracts\LegoDataServiceInterface;
use App\Data\Lego\LegoSetData;
use App\Models\Set;
use Illuminate\Database\Eloquent\Builder;

describe('GetSetAction', function (): void {
    it('should return existing set from database', function (): void {
        // arrange
        $existingSet = Mockery::mock(Set::class)->makePartial();
        $existingSet->id = 1;
        $existingSet->set_num = '75192-1';

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')
            ->with('set_num', '75192-1')
            ->once()
            ->andReturnSelf();
        $queryBuilder->shouldReceive('first')
            ->once()
            ->andReturn($existingSet);

        $set = Mockery::mock(Set::class);
        $set->shouldReceive('newQuery')
            ->once()
            ->andReturn($queryBuilder);

        $legoDataService = Mockery::mock(LegoDataServiceInterface::class);
        $legoDataService->shouldNotReceive('fetchSet');

        $action = new GetSetAction($legoDataService, $set);

        // act
        $result = $action->execute('75192-1');

        // assert
        expect($result)->toBe($existingSet);
    });

    it('should fetch from rebrickable when set not in database', function (): void {
        // arrange
        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')
            ->with('set_num', '75192-1')
            ->andReturnSelf();
        $queryBuilder->shouldReceive('first')
            ->andReturn(null);

        $createdSet = Mockery::mock(Set::class)->makePartial();
        $createdSet->id = 1;
        $createdSet->shouldReceive('save')->once();

        $set = Mockery::mock(Set::class);
        $set->shouldReceive('newQuery')
            ->andReturn($queryBuilder);
        $set->shouldReceive('newInstance')
            ->once()
            ->andReturn($createdSet);

        $legoDataService = Mockery::mock(LegoDataServiceInterface::class);
        $legoDataService->shouldReceive('fetchSet')
            ->with('75192-1')
            ->once()
            ->andReturn(new LegoSetData(
                setNum: '75192-1',
                name: 'Millennium Falcon',
                year: 2017,
                themeId: 158,
                numParts: 7541,
                imageUrl: 'https://example.com/75192.jpg',
            ));

        $action = new GetSetAction($legoDataService, $set);

        // act
        $result = $action->execute('75192-1');

        // assert
        expect($result)->toBe($createdSet);
        expect($result->set_num)->toBe('75192-1');
        expect($result->name)->toBe('Millennium Falcon');
        expect($result->year)->toBe(2017);
        expect($result->theme)->toBe('158');
        expect($result->num_parts)->toBe(7541);
        expect($result->image_url)->toBe('https://example.com/75192.jpg');
    });

    it('should handle null theme_id from rebrickable', function (): void {
        // arrange
        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')
            ->with('set_num', '10281-1')
            ->andReturnSelf();
        $queryBuilder->shouldReceive('first')
            ->andReturn(null);

        $createdSet = Mockery::mock(Set::class)->makePartial();
        $createdSet->shouldReceive('save')->once();

        $set = Mockery::mock(Set::class);
        $set->shouldReceive('newQuery')
            ->andReturn($queryBuilder);
        $set->shouldReceive('newInstance')
            ->once()
            ->andReturn($createdSet);

        $legoDataService = Mockery::mock(LegoDataServiceInterface::class);
        $legoDataService->shouldReceive('fetchSet')
            ->with('10281-1')
            ->once()
            ->andReturn(new LegoSetData(
                setNum: '10281-1',
                name: 'Bonsai Tree',
                year: 2021,
                themeId: null,
                numParts: 878,
                imageUrl: null,
            ));

        $action = new GetSetAction($legoDataService, $set);

        // act
        $result = $action->execute('10281-1');

        // assert
        expect($result)->toBe($createdSet);
        expect($result->set_num)->toBe('10281-1');
        expect($result->name)->toBe('Bonsai Tree');
        expect($result->year)->toBe(2021);
        expect($result->theme)->toBeNull();
        expect($result->num_parts)->toBe(878);
        expect($result->image_url)->toBeNull();
    });
});

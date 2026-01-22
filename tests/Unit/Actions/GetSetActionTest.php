<?php

declare(strict_types=1);

use App\Actions\GetSetAction;
use App\Models\Set;
use App\Services\RebrickableService;
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

        $rebrickableService = Mockery::mock(RebrickableService::class);
        $rebrickableService->shouldNotReceive('fetchSet');

        $action = new GetSetAction($rebrickableService, $set);

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

        $queryBuilder->shouldReceive('updateOrCreate')
            ->with(
                ['set_num' => '75192-1'],
                [
                    'name' => 'Millennium Falcon',
                    'year' => 2017,
                    'theme' => 158,
                    'num_parts' => 7541,
                    'image_url' => 'https://example.com/75192.jpg',
                ],
            )
            ->once()
            ->andReturn($createdSet);

        $set = Mockery::mock(Set::class);
        $set->shouldReceive('newQuery')
            ->andReturn($queryBuilder);

        $rebrickableService = Mockery::mock(RebrickableService::class);
        $rebrickableService->shouldReceive('fetchSet')
            ->with('75192-1')
            ->once()
            ->andReturn([
                'set_num' => '75192-1',
                'name' => 'Millennium Falcon',
                'year' => 2017,
                'theme_id' => 158,
                'num_parts' => 7541,
                'set_img_url' => 'https://example.com/75192.jpg',
            ]);

        $action = new GetSetAction($rebrickableService, $set);

        // act
        $result = $action->execute('75192-1');

        // assert
        expect($result)->toBe($createdSet);
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

        $queryBuilder->shouldReceive('updateOrCreate')
            ->with(
                ['set_num' => '10281-1'],
                [
                    'name' => 'Bonsai Tree',
                    'year' => 2021,
                    'theme' => null,
                    'num_parts' => 878,
                    'image_url' => null,
                ],
            )
            ->once()
            ->andReturn($createdSet);

        $set = Mockery::mock(Set::class);
        $set->shouldReceive('newQuery')
            ->andReturn($queryBuilder);

        $rebrickableService = Mockery::mock(RebrickableService::class);
        $rebrickableService->shouldReceive('fetchSet')
            ->with('10281-1')
            ->once()
            ->andReturn([
                'set_num' => '10281-1',
                'name' => 'Bonsai Tree',
                'year' => 2021,
                'theme_id' => null,
                'num_parts' => 878,
                'set_img_url' => null,
            ]);

        $action = new GetSetAction($rebrickableService, $set);

        // act
        $result = $action->execute('10281-1');

        // assert
        expect($result)->toBe($createdSet);
    });
});

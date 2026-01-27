<?php

declare(strict_types=1);

use App\Actions\GetSetPartsAction;
use App\Actions\Sync\StoreSetPartsAction;
use App\Actions\Sync\UpsertSetAction;
use App\Contracts\LegoDataServiceInterface;
use App\Data\Lego\LegoSetData;
use App\Data\Lego\LegoSetPartData;
use App\Models\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

describe('GetSetPartsAction', function (): void {
    it('should return existing set from database when set has parts', function (): void {
        // arrange
        $setPartsRelation = Mockery::mock(HasMany::class);
        $setPartsRelation->shouldReceive('exists')->once()->andReturn(true);

        $existingSet = Mockery::mock(Set::class)->makePartial();
        $existingSet->id = 1;
        $existingSet->set_num = '75192-1';
        $existingSet->shouldReceive('setParts')->once()->andReturn($setPartsRelation);

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->with('set_num', '75192-1')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('first')->once()->andReturn($existingSet);

        $set = Mockery::mock(Set::class);
        $set->shouldReceive('newQuery')->once()->andReturn($queryBuilder);

        $legoDataService = Mockery::mock(LegoDataServiceInterface::class);
        $legoDataService->shouldNotReceive('fetchSet');
        $legoDataService->shouldNotReceive('fetchSetParts');

        $upsertSetAction = Mockery::mock(UpsertSetAction::class);
        $upsertSetAction->shouldNotReceive('execute');

        $storeSetPartsAction = Mockery::mock(StoreSetPartsAction::class);
        $storeSetPartsAction->shouldNotReceive('execute');

        $action = new GetSetPartsAction($legoDataService, $upsertSetAction, $storeSetPartsAction, $set);

        // act
        $result = $action->execute('75192-1');

        // assert
        expect($result)->toBe($existingSet);
    });

    it('should fetch from API when set does not exist in database', function (): void {
        // arrange
        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->with('set_num', '75192-1')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('first')->once()->andReturn(null);

        $set = Mockery::mock(Set::class);
        $set->shouldReceive('newQuery')->once()->andReturn($queryBuilder);

        $legoSetData = new LegoSetData(
            setNum: '75192-1',
            name: 'Millennium Falcon',
            year: 2017,
            themeId: 158,
            numParts: 7541,
            imageUrl: 'https://example.com/75192.jpg',
        );

        $legoSetPartData = LegoSetPartData::fromArray([
            'part' => ['part_num' => '3001', 'name' => 'Brick 2 x 4', 'part_cat_id' => 11, 'part_img_url' => null],
            'color' => ['id' => 1, 'name' => 'White', 'rgb' => 'FFFFFF', 'is_trans' => false],
            'quantity' => 5,
            'is_spare' => false,
            'element_id' => '300101',
        ]);

        $legoDataService = Mockery::mock(LegoDataServiceInterface::class);
        $legoDataService->shouldReceive('fetchSet')
            ->with('75192-1')
            ->once()
            ->andReturn($legoSetData);
        $legoDataService->shouldReceive('fetchSetParts')
            ->with('75192-1')
            ->once()
            ->andReturn([$legoSetPartData]);

        $createdSet = Mockery::mock(Set::class);

        $upsertSetAction = Mockery::mock(UpsertSetAction::class);
        $upsertSetAction->shouldReceive('execute')
            ->with($legoSetData)
            ->once()
            ->andReturn($createdSet);

        $storeSetPartsAction = Mockery::mock(StoreSetPartsAction::class);
        $storeSetPartsAction->shouldReceive('execute')
            ->with($createdSet, Mockery::type('array'))
            ->once();

        $action = new GetSetPartsAction($legoDataService, $upsertSetAction, $storeSetPartsAction, $set);

        // act
        $result = $action->execute('75192-1');

        // assert
        expect($result)->toBe($createdSet);
    });

    it('should fetch from API when set exists but has no parts', function (): void {
        // arrange
        $setPartsRelation = Mockery::mock(HasMany::class);
        $setPartsRelation->shouldReceive('exists')->once()->andReturn(false);

        $existingSet = Mockery::mock(Set::class)->makePartial();
        $existingSet->id = 1;
        $existingSet->set_num = '75192-1';
        $existingSet->shouldReceive('setParts')->once()->andReturn($setPartsRelation);

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->with('set_num', '75192-1')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('first')->once()->andReturn($existingSet);

        $set = Mockery::mock(Set::class);
        $set->shouldReceive('newQuery')->once()->andReturn($queryBuilder);

        $legoSetData = new LegoSetData(
            setNum: '75192-1',
            name: 'Millennium Falcon',
            year: 2017,
            themeId: 158,
            numParts: 7541,
            imageUrl: 'https://example.com/75192.jpg',
        );

        $legoDataService = Mockery::mock(LegoDataServiceInterface::class);
        $legoDataService->shouldReceive('fetchSet')
            ->with('75192-1')
            ->once()
            ->andReturn($legoSetData);
        $legoDataService->shouldReceive('fetchSetParts')
            ->with('75192-1')
            ->once()
            ->andReturn([]);

        $upsertedSet = Mockery::mock(Set::class)->makePartial();
        $upsertedSet->id = 1;
        $upsertedSet->set_num = '75192-1';

        $upsertSetAction = Mockery::mock(UpsertSetAction::class);
        $upsertSetAction->shouldReceive('execute')
            ->with($legoSetData)
            ->once()
            ->andReturn($upsertedSet);

        $storeSetPartsAction = Mockery::mock(StoreSetPartsAction::class);
        $storeSetPartsAction->shouldReceive('execute')
            ->with($upsertedSet, [])
            ->once();

        $action = new GetSetPartsAction($legoDataService, $upsertSetAction, $storeSetPartsAction, $set);

        // act
        $result = $action->execute('75192-1');

        // assert
        expect($result)->toBe($upsertedSet);
    });
});

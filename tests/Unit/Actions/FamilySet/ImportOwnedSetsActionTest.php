<?php

declare(strict_types=1);

use App\Actions\FamilySet\ImportOwnedSetsAction;
use App\Actions\Sync\UpsertSetAction;
use App\Contracts\LegoDataServiceInterface;
use App\Data\ImportOwnedSetsResultData;
use App\Data\Lego\LegoSetData;
use App\Data\Lego\RebrickableUserSetData;
use App\Enums\FamilySetStatus;
use App\Exceptions\MissingRebrickableTokenException;
use App\Models\Family;
use App\Models\FamilySet;
use App\Models\Set;
use Illuminate\Database\Eloquent\Builder;

describe('ImportOwnedSetsAction', function (): void {
    it('should throw MissingRebrickableTokenException when family has no token', function (): void {
        // arrange
        $legoDataService = Mockery::mock(LegoDataServiceInterface::class);
        $upsertSetAction = Mockery::mock(UpsertSetAction::class);
        $familySetModel = Mockery::mock(FamilySet::class);

        $family = Mockery::mock(Family::class)->makePartial();
        $family->id = 1;
        $family->rebrickable_user_token = null;

        $action = new ImportOwnedSetsAction($legoDataService, $upsertSetAction, $familySetModel);

        // act & assert
        expect(fn (): ImportOwnedSetsResultData => $action->execute($family))
            ->toThrow(MissingRebrickableTokenException::class);
    });

    it('should fetch user sets from the service using the family token', function (): void {
        // arrange
        $legoDataService = Mockery::mock(LegoDataServiceInterface::class);
        $legoDataService->shouldReceive('fetchUserSets')
            ->with('user-token-123')
            ->once()
            ->andReturn([]);

        $upsertSetAction = Mockery::mock(UpsertSetAction::class);
        $familySetModel = Mockery::mock(FamilySet::class);

        $family = Mockery::mock(Family::class)->makePartial();
        $family->id = 1;
        $family->rebrickable_user_token = 'user-token-123';

        $action = new ImportOwnedSetsAction($legoDataService, $upsertSetAction, $familySetModel);

        // act
        $action->execute($family);

        // assert - Mockery expectations verify the interaction
    });

    it('should create new family sets for sets not already owned', function (): void {
        // arrange
        $legoSetData = new LegoSetData(
            setNum: '75192-1',
            name: 'Millennium Falcon',
            year: 2017,
            themeId: 158,
            numParts: 7541,
            imageUrl: 'https://example.com/image.jpg',
        );
        $userSetData = new RebrickableUserSetData(set: $legoSetData, quantity: 2);

        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 42;

        $legoDataService = Mockery::mock(LegoDataServiceInterface::class);
        $legoDataService->shouldReceive('fetchUserSets')->andReturn([$userSetData]);

        $upsertSetAction = Mockery::mock(UpsertSetAction::class);
        $upsertSetAction->shouldReceive('execute')
            ->with($legoSetData)
            ->once()
            ->andReturn($set);

        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('save')->once();

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->with('family_id', 1)->andReturnSelf();
        $queryBuilder->shouldReceive('where')->with('set_id', 42)->andReturnSelf();
        $queryBuilder->shouldReceive('count')->andReturn(0);

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->shouldReceive('newQuery')->andReturn($queryBuilder);
        $familySetModel->shouldReceive('newInstance')->andReturn($familySet);

        $family = Mockery::mock(Family::class)->makePartial();
        $family->id = 1;
        $family->rebrickable_user_token = 'user-token-123';

        $action = new ImportOwnedSetsAction($legoDataService, $upsertSetAction, $familySetModel);

        // act
        $action->execute($family);

        // assert
        expect($familySet->family_id)->toBe(1);
        expect($familySet->set_id)->toBe(42);
        expect($familySet->quantity)->toBe(2);
        expect($familySet->status)->toBe(FamilySetStatus::Sealed);
    });

    it('should update quantity for existing family sets when exactly one exists', function (): void {
        // arrange
        $legoSetData = new LegoSetData(
            setNum: '75192-1',
            name: 'Millennium Falcon',
            year: 2017,
            themeId: 158,
            numParts: 7541,
            imageUrl: 'https://example.com/image.jpg',
        );
        $userSetData = new RebrickableUserSetData(set: $legoSetData, quantity: 3);

        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 42;

        $existingFamilySet = Mockery::mock(FamilySet::class)->makePartial();
        $existingFamilySet->quantity = 1;
        $existingFamilySet->shouldReceive('save')->once();

        $legoDataService = Mockery::mock(LegoDataServiceInterface::class);
        $legoDataService->shouldReceive('fetchUserSets')->andReturn([$userSetData]);

        $upsertSetAction = Mockery::mock(UpsertSetAction::class);
        $upsertSetAction->shouldReceive('execute')->andReturn($set);

        $countQueryBuilder = Mockery::mock(Builder::class);
        $countQueryBuilder->shouldReceive('where')->with('family_id', 1)->andReturnSelf();
        $countQueryBuilder->shouldReceive('where')->with('set_id', 42)->andReturnSelf();
        $countQueryBuilder->shouldReceive('count')->andReturn(1);

        $firstQueryBuilder = Mockery::mock(Builder::class);
        $firstQueryBuilder->shouldReceive('where')->with('family_id', 1)->andReturnSelf();
        $firstQueryBuilder->shouldReceive('where')->with('set_id', 42)->andReturnSelf();
        $firstQueryBuilder->shouldReceive('first')->andReturn($existingFamilySet);

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->shouldReceive('newQuery')
            ->twice()
            ->andReturn($countQueryBuilder, $firstQueryBuilder);
        $familySetModel->shouldReceive('newInstance')->never();

        $family = Mockery::mock(Family::class)->makePartial();
        $family->id = 1;
        $family->rebrickable_user_token = 'user-token-123';

        $action = new ImportOwnedSetsAction($legoDataService, $upsertSetAction, $familySetModel);

        // act
        $action->execute($family);

        // assert
        expect($existingFamilySet->quantity)->toBe(3);
    });

    it('should return correct counts for created and updated sets', function (): void {
        // arrange
        $legoSetData1 = new LegoSetData(
            setNum: '75192-1',
            name: 'Millennium Falcon',
            year: 2017,
            themeId: 158,
            numParts: 7541,
            imageUrl: null,
        );
        $legoSetData2 = new LegoSetData(
            setNum: '10179-1',
            name: 'Ultimate Collectors Millennium Falcon',
            year: 2007,
            themeId: 158,
            numParts: 5195,
            imageUrl: null,
        );
        $userSetData1 = new RebrickableUserSetData(set: $legoSetData1, quantity: 1);
        $userSetData2 = new RebrickableUserSetData(set: $legoSetData2, quantity: 2);

        $set1 = Mockery::mock(Set::class)->makePartial();
        $set1->id = 1;

        $set2 = Mockery::mock(Set::class)->makePartial();
        $set2->id = 2;

        $legoDataService = Mockery::mock(LegoDataServiceInterface::class);
        $legoDataService->shouldReceive('fetchUserSets')->andReturn([$userSetData1, $userSetData2]);

        $upsertSetAction = Mockery::mock(UpsertSetAction::class);
        $upsertSetAction->shouldReceive('execute')->with($legoSetData1)->andReturn($set1);
        $upsertSetAction->shouldReceive('execute')->with($legoSetData2)->andReturn($set2);

        $existingFamilySet = Mockery::mock(FamilySet::class)->makePartial();
        $existingFamilySet->shouldReceive('save');

        $newFamilySet = Mockery::mock(FamilySet::class)->makePartial();
        $newFamilySet->shouldReceive('save');

        // First set: count returns 1 (exists), then first returns the existing set
        $countQueryBuilder1 = Mockery::mock(Builder::class);
        $countQueryBuilder1->shouldReceive('where')->with('family_id', 1)->andReturnSelf();
        $countQueryBuilder1->shouldReceive('where')->with('set_id', 1)->andReturnSelf();
        $countQueryBuilder1->shouldReceive('count')->andReturn(1);

        $firstQueryBuilder1 = Mockery::mock(Builder::class);
        $firstQueryBuilder1->shouldReceive('where')->with('family_id', 1)->andReturnSelf();
        $firstQueryBuilder1->shouldReceive('where')->with('set_id', 1)->andReturnSelf();
        $firstQueryBuilder1->shouldReceive('first')->andReturn($existingFamilySet);

        // Second set: count returns 0 (doesn't exist)
        $countQueryBuilder2 = Mockery::mock(Builder::class);
        $countQueryBuilder2->shouldReceive('where')->with('family_id', 1)->andReturnSelf();
        $countQueryBuilder2->shouldReceive('where')->with('set_id', 2)->andReturnSelf();
        $countQueryBuilder2->shouldReceive('count')->andReturn(0);

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->shouldReceive('newQuery')
            ->times(3)
            ->andReturn($countQueryBuilder1, $firstQueryBuilder1, $countQueryBuilder2);
        $familySetModel->shouldReceive('newInstance')->once()->andReturn($newFamilySet);

        $family = Mockery::mock(Family::class)->makePartial();
        $family->id = 1;
        $family->rebrickable_user_token = 'user-token-123';

        $action = new ImportOwnedSetsAction($legoDataService, $upsertSetAction, $familySetModel);

        // act
        $result = $action->execute($family);

        // assert
        expect($result)->toBeInstanceOf(ImportOwnedSetsResultData::class);
        expect($result->created)->toBe(1);
        expect($result->updated)->toBe(1);
        expect($result->skipped)->toBe(0);
        expect($result->total)->toBe(2);
    });

    it('should return zero counts when no sets are found', function (): void {
        // arrange
        $legoDataService = Mockery::mock(LegoDataServiceInterface::class);
        $legoDataService->shouldReceive('fetchUserSets')->andReturn([]);

        $upsertSetAction = Mockery::mock(UpsertSetAction::class);
        $familySetModel = Mockery::mock(FamilySet::class);

        $family = Mockery::mock(Family::class)->makePartial();
        $family->id = 1;
        $family->rebrickable_user_token = 'user-token-123';

        $action = new ImportOwnedSetsAction($legoDataService, $upsertSetAction, $familySetModel);

        // act
        $result = $action->execute($family);

        // assert
        expect($result->created)->toBe(0);
        expect($result->updated)->toBe(0);
        expect($result->skipped)->toBe(0);
        expect($result->total)->toBe(0);
        expect($result->skippedSetNums)->toBe([]);
    });

    it('should skip sets when multiple family sets exist for the same set', function (): void {
        // arrange
        $legoSetData = new LegoSetData(
            setNum: '75192-1',
            name: 'Millennium Falcon',
            year: 2017,
            themeId: 158,
            numParts: 7541,
            imageUrl: 'https://example.com/image.jpg',
        );
        $userSetData = new RebrickableUserSetData(set: $legoSetData, quantity: 3);

        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 42;

        $legoDataService = Mockery::mock(LegoDataServiceInterface::class);
        $legoDataService->shouldReceive('fetchUserSets')->andReturn([$userSetData]);

        $upsertSetAction = Mockery::mock(UpsertSetAction::class);
        $upsertSetAction->shouldReceive('execute')
            ->with($legoSetData)
            ->once()
            ->andReturn($set);

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->with('family_id', 1)->andReturnSelf();
        $queryBuilder->shouldReceive('where')->with('set_id', 42)->andReturnSelf();
        $queryBuilder->shouldReceive('count')->andReturn(2); // Multiple rows exist

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->shouldReceive('newQuery')->andReturn($queryBuilder);
        $familySetModel->shouldReceive('newInstance')->never();

        $family = Mockery::mock(Family::class)->makePartial();
        $family->id = 1;
        $family->rebrickable_user_token = 'user-token-123';

        $action = new ImportOwnedSetsAction($legoDataService, $upsertSetAction, $familySetModel);

        // act
        $result = $action->execute($family);

        // assert
        expect($result->created)->toBe(0);
        expect($result->updated)->toBe(0);
        expect($result->skipped)->toBe(1);
        expect($result->total)->toBe(0);
        expect($result->skippedSetNums)->toBe(['75192-1']);
    });
});

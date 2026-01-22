<?php

declare(strict_types=1);

use App\Actions\FamilySet\AddSetToFamilyAction;
use App\Actions\FamilySet\UpdateFamilySetAction;
use App\DataTransferObjects\CreateFamilySetData;
use App\DataTransferObjects\UpdateFamilySetData;
use App\Enums\FamilySetStatus;
use App\Models\Family;
use App\Models\FamilySet;
use App\Models\Set;
use App\Services\RebrickableService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

describe('AddSetToFamilyAction', function (): void {
    it('should fetch set from rebrickable service', function (): void {
        // arrange
        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 1;

        $rebrickableService = Mockery::mock(RebrickableService::class);
        $rebrickableService->shouldReceive('getSet')
            ->with('75192-1')
            ->once()
            ->andReturn($set);

        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('load')->with('set')->once();

        $familySetsRelation = Mockery::mock(HasMany::class);
        $familySetsRelation->shouldReceive('create')
            ->with(['set_id' => 1])
            ->once()
            ->andReturn($familySet);

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('familySets')->once()->andReturn($familySetsRelation);

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')
            ->once()
            ->andReturn($familySet);

        $action = new AddSetToFamilyAction($rebrickableService, $updateAction);
        $data = new CreateFamilySetData(
            setNum: '75192-1',
            quantity: 2,
            status: FamilySetStatus::Built,
            purchaseDate: null,
            notes: null,
        );

        // act
        $action->execute($family, $data);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });

    it('should create family set via relationship', function (): void {
        // arrange
        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 42;

        $rebrickableService = Mockery::mock(RebrickableService::class);
        $rebrickableService->shouldReceive('getSet')->andReturn($set);

        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('load')->with('set');

        $familySetsRelation = Mockery::mock(HasMany::class);
        $familySetsRelation->shouldReceive('create')
            ->with(['set_id' => 42])
            ->once()
            ->andReturn($familySet);

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('familySets')->andReturn($familySetsRelation);

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')->andReturn($familySet);

        $action = new AddSetToFamilyAction($rebrickableService, $updateAction);
        $data = new CreateFamilySetData(
            setNum: '75192-1',
            quantity: 1,
            status: FamilySetStatus::Sealed,
            purchaseDate: null,
            notes: null,
        );

        // act
        $action->execute($family, $data);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });

    it('should delegate to update action with correct data', function (): void {
        // arrange
        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 1;

        $rebrickableService = Mockery::mock(RebrickableService::class);
        $rebrickableService->shouldReceive('getSet')->andReturn($set);

        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('load')->with('set');

        $familySetsRelation = Mockery::mock(HasMany::class);
        $familySetsRelation->shouldReceive('create')->andReturn($familySet);

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('familySets')->andReturn($familySetsRelation);

        $purchaseDate = Carbon::parse('2024-01-15');

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')
            ->withArgs(fn (FamilySet $fs, UpdateFamilySetData $data): bool => $fs === $familySet
                && $data->quantity === 2
                && $data->status === FamilySetStatus::Built
                && $data->purchaseDate === $purchaseDate
                && $data->notes === 'Test notes')
            ->once()
            ->andReturn($familySet);

        $action = new AddSetToFamilyAction($rebrickableService, $updateAction);
        $data = new CreateFamilySetData(
            setNum: '75192-1',
            quantity: 2,
            status: FamilySetStatus::Built,
            purchaseDate: $purchaseDate,
            notes: 'Test notes',
        );

        // act
        $action->execute($family, $data);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });

    it('should load the set relationship', function (): void {
        // arrange
        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 1;

        $rebrickableService = Mockery::mock(RebrickableService::class);
        $rebrickableService->shouldReceive('getSet')->andReturn($set);

        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('load')
            ->with('set')
            ->once();

        $familySetsRelation = Mockery::mock(HasMany::class);
        $familySetsRelation->shouldReceive('create')->andReturn($familySet);

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('familySets')->andReturn($familySetsRelation);

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')->andReturn($familySet);

        $action = new AddSetToFamilyAction($rebrickableService, $updateAction);
        $data = new CreateFamilySetData(
            setNum: '75192-1',
            quantity: 1,
            status: FamilySetStatus::Sealed,
            purchaseDate: null,
            notes: null,
        );

        // act
        $action->execute($family, $data);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });

    it('should return the family set from update action', function (): void {
        // arrange
        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 1;

        $rebrickableService = Mockery::mock(RebrickableService::class);
        $rebrickableService->shouldReceive('getSet')->andReturn($set);

        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('load')->with('set');

        $familySetsRelation = Mockery::mock(HasMany::class);
        $familySetsRelation->shouldReceive('create')->andReturn($familySet);

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('familySets')->andReturn($familySetsRelation);

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')->andReturn($familySet);

        $action = new AddSetToFamilyAction($rebrickableService, $updateAction);
        $data = new CreateFamilySetData(
            setNum: '75192-1',
            quantity: 1,
            status: FamilySetStatus::Sealed,
            purchaseDate: null,
            notes: null,
        );

        // act
        $result = $action->execute($family, $data);

        // assert
        expect($result)->toBe($familySet);
    });
});

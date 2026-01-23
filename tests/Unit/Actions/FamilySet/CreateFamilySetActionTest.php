<?php

declare(strict_types=1);

use App\Actions\FamilySet\CreateFamilySetAction;
use App\Actions\FamilySet\UpdateFamilySetAction;
use App\Actions\GetSetAction;
use App\DataTransferObjects\CreateFamilySetData;
use App\DataTransferObjects\UpdateFamilySetData;
use App\Enums\FamilySetStatus;
use App\Models\Family;
use App\Models\FamilySet;
use App\Models\Set;
use Carbon\Carbon;

describe('CreateFamilySetAction', function (): void {
    it('should fetch set using GetSetAction', function (): void {
        // arrange
        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 1;

        $getSetAction = Mockery::mock(GetSetAction::class);
        $getSetAction->shouldReceive('execute')
            ->with('75192-1')
            ->once()
            ->andReturn($set);

        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('save')->once();
        $familySet->shouldReceive('load')->with('set')->once();

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->shouldReceive('newInstance')
            ->once()
            ->andReturn($familySet);

        $family = Mockery::mock(Family::class)->makePartial();
        $family->id = 10;

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')
            ->once()
            ->andReturn($familySet);

        $action = new CreateFamilySetAction($getSetAction, $updateAction, $familySetModel);
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

    it('should create family set with correct family_id and set_id', function (): void {
        // arrange
        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 42;

        $getSetAction = Mockery::mock(GetSetAction::class);
        $getSetAction->shouldReceive('execute')->andReturn($set);

        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('save')->once();
        $familySet->shouldReceive('load')->with('set');

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->shouldReceive('newInstance')
            ->once()
            ->andReturn($familySet);

        $family = Mockery::mock(Family::class)->makePartial();
        $family->id = 99;

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')->andReturn($familySet);

        $action = new CreateFamilySetAction($getSetAction, $updateAction, $familySetModel);
        $data = new CreateFamilySetData(
            setNum: '75192-1',
            quantity: 1,
            status: FamilySetStatus::Sealed,
            purchaseDate: null,
            notes: null,
        );

        // act
        $action->execute($family, $data);

        // assert
        expect($familySet->family_id)->toBe(99);
        expect($familySet->set_id)->toBe(42);
    });

    it('should delegate to update action with correct data', function (): void {
        // arrange
        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 1;

        $getSetAction = Mockery::mock(GetSetAction::class);
        $getSetAction->shouldReceive('execute')->andReturn($set);

        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('save');
        $familySet->shouldReceive('load')->with('set');

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->shouldReceive('newInstance')->andReturn($familySet);

        $family = Mockery::mock(Family::class)->makePartial();
        $family->id = 10;

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

        $action = new CreateFamilySetAction($getSetAction, $updateAction, $familySetModel);
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

        $getSetAction = Mockery::mock(GetSetAction::class);
        $getSetAction->shouldReceive('execute')->andReturn($set);

        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('save');
        $familySet->shouldReceive('load')
            ->with('set')
            ->once();

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->shouldReceive('newInstance')->andReturn($familySet);

        $family = Mockery::mock(Family::class)->makePartial();
        $family->id = 10;

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')->andReturn($familySet);

        $action = new CreateFamilySetAction($getSetAction, $updateAction, $familySetModel);
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

        $getSetAction = Mockery::mock(GetSetAction::class);
        $getSetAction->shouldReceive('execute')->andReturn($set);

        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('save');
        $familySet->shouldReceive('load')->with('set');

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->shouldReceive('newInstance')->andReturn($familySet);

        $family = Mockery::mock(Family::class)->makePartial();
        $family->id = 10;

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')->andReturn($familySet);

        $action = new CreateFamilySetAction($getSetAction, $updateAction, $familySetModel);
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

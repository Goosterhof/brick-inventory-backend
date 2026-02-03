<?php

declare(strict_types=1);

use App\Actions\FamilySet\CreateFamilySetAction;
use App\Actions\FamilySet\UpdateFamilySetAction;
use App\Actions\GetSetAction;
use App\Contracts\FamilySet\CreateFamilySetInterface;
use App\Contracts\FamilySet\UpdateFamilySetInterface;
use App\Enums\FamilySetStatus;
use App\Models\Family;
use App\Models\FamilySet;
use App\Models\Set;
use Illuminate\Support\Facades\Date;

describe('CreateFamilySetAction', function (): void {
    it('should fetch set using GetSetAction', function (): void {
        // arrange
        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(1);

        $getSetAction = Mockery::mock(GetSetAction::class);
        $getSetAction->shouldReceive('execute')
            ->with('75192-1')
            ->once()
            ->andReturn($set);

        $familySetSavedValues = [];
        $familySet = Mockery::mock(FamilySet::class);
        $familySet->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$familySetSavedValues): void {
            $familySetSavedValues[$key] = $value;
        });
        $familySet->allows('getAttribute')->andReturnUsing(function ($key) use (&$familySetSavedValues): mixed {
            return $familySetSavedValues[$key] ?? null;
        });
        $familySet->shouldReceive('save')->once();

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->shouldReceive('newInstance')
            ->once()
            ->andReturn($familySet);

        $family = Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(10);

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')
            ->once()
            ->andReturn($familySet);

        $action = new CreateFamilySetAction($getSetAction, $updateAction, $familySetModel);
        $data = new class implements CreateFamilySetInterface
        {
            public string $setNum = '75192-1';

            public int $quantity = 2;

            public FamilySetStatus $status = FamilySetStatus::Built;

            public ?DateTimeInterface $purchaseDate = null;

            public ?string $notes = null;
        };

        // act
        $action->execute($family, $data);

        // assert - Mockery expectations verify the interactions
    });

    it('should create family set with correct family_id and set_id', function (): void {
        // arrange
        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(42);

        $getSetAction = Mockery::mock(GetSetAction::class);
        $getSetAction->shouldReceive('execute')->andReturn($set);

        $familySetSavedValues = [];
        $familySet = Mockery::mock(FamilySet::class);
        $familySet->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$familySetSavedValues): void {
            $familySetSavedValues[$key] = $value;
        });
        $familySet->allows('getAttribute')->andReturnUsing(function ($key) use (&$familySetSavedValues): mixed {
            return $familySetSavedValues[$key] ?? null;
        });
        $familySet->shouldReceive('save')->once();

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->shouldReceive('newInstance')
            ->once()
            ->andReturn($familySet);

        $family = Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(99);

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')->andReturn($familySet);

        $action = new CreateFamilySetAction($getSetAction, $updateAction, $familySetModel);
        $data = new class implements CreateFamilySetInterface
        {
            public string $setNum = '75192-1';

            public int $quantity = 1;

            public FamilySetStatus $status = FamilySetStatus::Sealed;

            public ?DateTimeInterface $purchaseDate = null;

            public ?string $notes = null;
        };

        // act
        $action->execute($family, $data);

        // assert
        expect($familySetSavedValues['family_id'])->toBe(99);
        expect($familySetSavedValues['set_id'])->toBe(42);
    });

    it('should delegate to update action with correct data', function (): void {
        // arrange
        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(1);

        $getSetAction = Mockery::mock(GetSetAction::class);
        $getSetAction->shouldReceive('execute')->andReturn($set);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->allows('setAttribute');
        $familySet->allows('getAttribute');
        $familySet->shouldReceive('save');

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->shouldReceive('newInstance')->andReturn($familySet);

        $family = Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(10);

        $purchaseDate = Date::parse('2024-01-15');

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')
            ->withArgs(fn (FamilySet $fs, UpdateFamilySetInterface $updateFamilySet): bool => $fs === $familySet
                && $updateFamilySet->quantity === 2
                && $updateFamilySet->status === FamilySetStatus::Built
                && $updateFamilySet->purchaseDate === $purchaseDate
                && $updateFamilySet->notes === 'Test notes')
            ->once()
            ->andReturn($familySet);

        $action = new CreateFamilySetAction($getSetAction, $updateAction, $familySetModel);
        $data = new class($purchaseDate) implements CreateFamilySetInterface
        {
            public string $setNum = '75192-1';

            public int $quantity = 2;

            public FamilySetStatus $status = FamilySetStatus::Built;

            public ?string $notes = 'Test notes';

            public function __construct(
                public ?DateTimeInterface $purchaseDate,
            ) {}
        };

        // act
        $action->execute($family, $data);

        // assert - Mockery expectations verify the interactions
    });

    it('should return the family set from update action', function (): void {
        // arrange
        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(1);

        $getSetAction = Mockery::mock(GetSetAction::class);
        $getSetAction->shouldReceive('execute')->andReturn($set);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->allows('setAttribute');
        $familySet->allows('getAttribute');
        $familySet->shouldReceive('save');

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->shouldReceive('newInstance')->andReturn($familySet);

        $family = Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(10);

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')->andReturn($familySet);

        $action = new CreateFamilySetAction($getSetAction, $updateAction, $familySetModel);
        $data = new class implements CreateFamilySetInterface
        {
            public string $setNum = '75192-1';

            public int $quantity = 1;

            public FamilySetStatus $status = FamilySetStatus::Sealed;

            public ?DateTimeInterface $purchaseDate = null;

            public ?string $notes = null;
        };

        // act
        $result = $action->execute($family, $data);

        // assert
        expect($result)->toBe($familySet);
    });
});

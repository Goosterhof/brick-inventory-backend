<?php

declare(strict_types=1);

use App\Actions\FamilySet\AddSetToFamilyAction;
use App\Actions\FamilySet\UpdateFamilySetAction;
use App\Actions\GetSetAction;
use App\Contracts\FamilySet\CreateFamilySetInterface;
use App\Contracts\FamilySet\UpdateFamilySetInterface;
use App\Enums\FamilySetStatus;
use App\Models\Family;
use App\Models\FamilySet;
use App\Models\Set;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

describe('AddSetToFamilyAction', function (): void {
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

        $action = new AddSetToFamilyAction($getSetAction, $updateAction);
        $data = new class implements CreateFamilySetInterface
        {
            public string $setNum = '75192-1';

            public int $quantity = 2;

            public FamilySetStatus $status = FamilySetStatus::Built;

            public ?\DateTimeInterface $purchaseDate = null;

            public ?string $notes = null;
        };

        // act
        $action->execute($family, $data);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });

    it('should create family set via relationship', function (): void {
        // arrange
        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 42;

        $getSetAction = Mockery::mock(GetSetAction::class);
        $getSetAction->shouldReceive('execute')->andReturn($set);

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

        $action = new AddSetToFamilyAction($getSetAction, $updateAction);
        $data = new class implements CreateFamilySetInterface
        {
            public string $setNum = '75192-1';

            public int $quantity = 1;

            public FamilySetStatus $status = FamilySetStatus::Sealed;

            public ?\DateTimeInterface $purchaseDate = null;

            public ?string $notes = null;
        };

        // act
        $action->execute($family, $data);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });

    it('should delegate to update action with correct data', function (): void {
        // arrange
        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 1;

        $getSetAction = Mockery::mock(GetSetAction::class);
        $getSetAction->shouldReceive('execute')->andReturn($set);

        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('load')->with('set');

        $familySetsRelation = Mockery::mock(HasMany::class);
        $familySetsRelation->shouldReceive('create')->andReturn($familySet);

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('familySets')->andReturn($familySetsRelation);

        $purchaseDate = Carbon::parse('2024-01-15');

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')
            ->withArgs(fn (FamilySet $fs, UpdateFamilySetInterface $data): bool => $fs === $familySet
                && $data->quantity === 2
                && $data->status === FamilySetStatus::Built
                && $data->purchaseDate === $purchaseDate
                && $data->notes === 'Test notes')
            ->once()
            ->andReturn($familySet);

        $action = new AddSetToFamilyAction($getSetAction, $updateAction);
        $data = new class($purchaseDate) implements CreateFamilySetInterface
        {
            public string $setNum = '75192-1';

            public int $quantity = 2;

            public FamilySetStatus $status = FamilySetStatus::Built;

            public ?string $notes = 'Test notes';

            public function __construct(
                public ?\DateTimeInterface $purchaseDate,
            ) {}
        };

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
        $familySet->shouldReceive('load')
            ->with('set')
            ->once();

        $familySetsRelation = Mockery::mock(HasMany::class);
        $familySetsRelation->shouldReceive('create')->andReturn($familySet);

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('familySets')->andReturn($familySetsRelation);

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')->andReturn($familySet);

        $action = new AddSetToFamilyAction($getSetAction, $updateAction);
        $data = new class implements CreateFamilySetInterface
        {
            public string $setNum = '75192-1';

            public int $quantity = 1;

            public FamilySetStatus $status = FamilySetStatus::Sealed;

            public ?\DateTimeInterface $purchaseDate = null;

            public ?string $notes = null;
        };

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
        $familySet->shouldReceive('load')->with('set');

        $familySetsRelation = Mockery::mock(HasMany::class);
        $familySetsRelation->shouldReceive('create')->andReturn($familySet);

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('familySets')->andReturn($familySetsRelation);

        $updateAction = Mockery::mock(UpdateFamilySetAction::class);
        $updateAction->shouldReceive('execute')->andReturn($familySet);

        $action = new AddSetToFamilyAction($getSetAction, $updateAction);
        $data = new class implements CreateFamilySetInterface
        {
            public string $setNum = '75192-1';

            public int $quantity = 1;

            public FamilySetStatus $status = FamilySetStatus::Sealed;

            public ?\DateTimeInterface $purchaseDate = null;

            public ?string $notes = null;
        };

        // act
        $result = $action->execute($family, $data);

        // assert
        expect($result)->toBe($familySet);
    });
});

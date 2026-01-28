<?php

declare(strict_types=1);

use App\Actions\FamilySet\UpdateFamilySetAction;
use App\Contracts\FamilySet\UpdateFamilySetInterface;
use App\Enums\FamilySetStatus;
use App\Models\FamilySet;
use Illuminate\Support\Facades\Date;

describe('UpdateFamilySetAction', function (): void {
    it('should update all fields on the family set', function (): void {
        // arrange
        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('save')->once();

        $purchaseDate = Date::parse('2024-06-15');

        $action = new UpdateFamilySetAction;
        $data = new class($purchaseDate) implements UpdateFamilySetInterface
        {
            public int $quantity = 5;

            public FamilySetStatus $status = FamilySetStatus::Built;

            public ?string $notes = 'Updated notes';

            public function __construct(
                public ?DateTimeInterface $purchaseDate,
            ) {}
        };

        // act
        $result = $action->execute($familySet, $data);

        // assert
        expect($result)->toBe($familySet)
            ->and($familySet->quantity)->toBe(5)
            ->and($familySet->status)->toBe(FamilySetStatus::Built)
            ->and($familySet->purchase_date->format('Y-m-d'))->toBe('2024-06-15')
            ->and($familySet->notes)->toBe('Updated notes');
    });

    it('should set purchase_date to null when not provided', function (): void {
        // arrange
        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('save')->once();

        $action = new UpdateFamilySetAction;
        $data = new class implements UpdateFamilySetInterface
        {
            public int $quantity = 3;

            public FamilySetStatus $status = FamilySetStatus::InProgress;

            public ?DateTimeInterface $purchaseDate = null;

            public ?string $notes = null;
        };

        // act
        $action->execute($familySet, $data);

        // assert
        expect($familySet->quantity)->toBe(3)
            ->and($familySet->status)->toBe(FamilySetStatus::InProgress)
            ->and($familySet->purchase_date)->toBeNull()
            ->and($familySet->notes)->toBeNull();
    });

    it('should call save on the family set', function (): void {
        // arrange
        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('save')->once();

        $action = new UpdateFamilySetAction;
        $data = new class implements UpdateFamilySetInterface
        {
            public int $quantity = 1;

            public FamilySetStatus $status = FamilySetStatus::Sealed;

            public ?DateTimeInterface $purchaseDate = null;

            public ?string $notes = null;
        };

        // act
        $action->execute($familySet, $data);

        // assert - Mockery expectations verify the interactions
    });

    it('should return the same family set instance', function (): void {
        // arrange
        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('save')->once();

        $action = new UpdateFamilySetAction;
        $data = new class implements UpdateFamilySetInterface
        {
            public int $quantity = 3;

            public FamilySetStatus $status = FamilySetStatus::Sealed;

            public ?DateTimeInterface $purchaseDate = null;

            public ?string $notes = null;
        };

        // act
        $result = $action->execute($familySet, $data);

        // assert
        expect($result)->toBe($familySet);
    });
});

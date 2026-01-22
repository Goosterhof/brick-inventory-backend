<?php

declare(strict_types=1);

use App\Actions\FamilySet\UpdateFamilySetAction;
use App\DataTransferObjects\UpdateFamilySetData;
use App\Enums\FamilySetStatus;
use App\Models\FamilySet;
use Carbon\Carbon;

describe('UpdateFamilySetAction', function (): void {
    it('should update all fields on the family set', function (): void {
        // arrange
        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('save')->once();

        $action = new UpdateFamilySetAction;
        $data = new UpdateFamilySetData(
            quantity: 5,
            status: FamilySetStatus::Built,
            purchaseDate: Carbon::parse('2024-06-15'),
            notes: 'Updated notes',
        );

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
        $data = new UpdateFamilySetData(
            quantity: 3,
            status: FamilySetStatus::InProgress,
            purchaseDate: null,
            notes: null,
        );

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
        $data = new UpdateFamilySetData(
            quantity: 1,
            status: FamilySetStatus::Sealed,
            purchaseDate: null,
            notes: null,
        );

        // act
        $action->execute($familySet, $data);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });

    it('should return the same family set instance', function (): void {
        // arrange
        $familySet = Mockery::mock(FamilySet::class)->makePartial();
        $familySet->shouldReceive('save')->once();

        $action = new UpdateFamilySetAction;
        $data = new UpdateFamilySetData(
            quantity: 3,
            status: FamilySetStatus::Sealed,
            purchaseDate: null,
            notes: null,
        );

        // act
        $result = $action->execute($familySet, $data);

        // assert
        expect($result)->toBe($familySet);
    });
});

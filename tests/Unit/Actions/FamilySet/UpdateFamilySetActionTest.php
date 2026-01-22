<?php

declare(strict_types=1);

use App\Actions\FamilySet\UpdateFamilySetAction;
use App\DataTransferObjects\UpdateFamilySetData;
use App\Enums\FamilySetStatus;
use App\Models\FamilySet;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UpdateFamilySetAction', function (): void {
    it('should update all fields', function (): void {
        // arrange
        $familySet = FamilySet::factory()->create([
            'quantity' => 1,
            'status' => FamilySetStatus::Sealed,
            'purchase_date' => null,
            'notes' => null,
        ]);

        $action = new UpdateFamilySetAction;
        $data = new UpdateFamilySetData(
            quantity: 5,
            status: FamilySetStatus::Built,
            purchaseDate: Carbon::parse('2024-06-15'),
            notes: 'Updated notes',
        );

        // act
        $updatedFamilySet = $action->execute($familySet, $data);

        // assert
        expect($updatedFamilySet->quantity)->toBe(5)
            ->and($updatedFamilySet->status)->toBe(FamilySetStatus::Built)
            ->and($updatedFamilySet->purchase_date->format('Y-m-d'))->toBe('2024-06-15')
            ->and($updatedFamilySet->notes)->toBe('Updated notes');
    });

    it('should set nullable fields to null', function (): void {
        // arrange
        $familySet = FamilySet::factory()->create([
            'quantity' => 2,
            'status' => FamilySetStatus::Sealed,
            'purchase_date' => Carbon::parse('2024-01-01'),
            'notes' => 'Original notes',
        ]);

        $action = new UpdateFamilySetAction;
        $data = new UpdateFamilySetData(
            quantity: 3,
            status: FamilySetStatus::InProgress,
            purchaseDate: null,
            notes: null,
        );

        // act
        $updatedFamilySet = $action->execute($familySet, $data);

        // assert
        expect($updatedFamilySet->quantity)->toBe(3)
            ->and($updatedFamilySet->status)->toBe(FamilySetStatus::InProgress)
            ->and($updatedFamilySet->purchase_date)->toBeNull()
            ->and($updatedFamilySet->notes)->toBeNull();
    });

    it('should persist changes to database', function (): void {
        // arrange
        $familySet = FamilySet::factory()->create([
            'quantity' => 1,
            'status' => FamilySetStatus::Sealed,
        ]);

        $action = new UpdateFamilySetAction;
        $data = new UpdateFamilySetData(
            quantity: 10,
            status: FamilySetStatus::Built,
            purchaseDate: null,
            notes: null,
        );

        // act
        $action->execute($familySet, $data);

        // assert
        $familySet->refresh();
        expect($familySet->quantity)->toBe(10)
            ->and($familySet->status)->toBe(FamilySetStatus::Built);
    });

    it('should return the same family set instance', function (): void {
        // arrange
        $familySet = FamilySet::factory()->create();

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
        expect($result->id)->toBe($familySet->id);
    });
});

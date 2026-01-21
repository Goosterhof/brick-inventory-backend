<?php

declare(strict_types=1);

use App\Actions\FamilySet\RemoveFamilySetAction;
use App\Models\FamilySet;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('RemoveFamilySetAction', function (): void {
    it('should delete the family set', function (): void {
        // arrange
        $familySet = FamilySet::factory()->create();
        $familySetId = $familySet->id;

        $action = new RemoveFamilySetAction;

        // act
        $action->execute($familySet);

        // assert
        expect(FamilySet::find($familySetId))->toBeNull();
    });

    it('should not affect other family sets', function (): void {
        // arrange
        $familySetToDelete = FamilySet::factory()->create();
        $familySetToKeep = FamilySet::factory()->create();

        $action = new RemoveFamilySetAction;

        // act
        $action->execute($familySetToDelete);

        // assert
        expect(FamilySet::find($familySetToKeep->id))->not->toBeNull();
    });
});

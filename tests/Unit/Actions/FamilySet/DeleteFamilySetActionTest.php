<?php

declare(strict_types=1);

use App\Actions\FamilySet\DeleteFamilySetAction;
use App\Models\FamilySet;

describe('DeleteFamilySetAction', function (): void {
    it('should call delete on the family set', function (): void {
        // arrange
        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('delete')->once();

        $action = new DeleteFamilySetAction;

        // act
        $action->execute($familySet);

        // assert - Mockery expectations verify the interactions
    });
});

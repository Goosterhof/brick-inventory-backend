<?php

declare(strict_types=1);

use App\Actions\FamilySet\RemoveFamilySetAction;
use App\Models\FamilySet;

describe('RemoveFamilySetAction', function (): void {
    it('should call delete on the family set', function (): void {
        // arrange
        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('delete')->once();

        $action = new RemoveFamilySetAction;

        // act
        $action->execute($familySet);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });
});

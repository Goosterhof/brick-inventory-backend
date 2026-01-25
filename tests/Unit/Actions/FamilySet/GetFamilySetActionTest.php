<?php

declare(strict_types=1);

use App\Actions\FamilySet\GetFamilySetAction;
use App\Models\FamilySet;

describe('GetFamilySetAction', function (): void {
    it('should load the set relationship', function (): void {
        // arrange
        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('load')
            ->with('set')
            ->once();

        $action = new GetFamilySetAction;

        // act
        $action->execute($familySet);

        // assert - verification happens via Mockery expectations
        expect(true)->toBeTrue();
    });

    it('should return the same family set instance', function (): void {
        // arrange
        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('load')->with('set');

        $action = new GetFamilySetAction;

        // act
        $result = $action->execute($familySet);

        // assert
        expect($result)->toBe($familySet);
    });
});

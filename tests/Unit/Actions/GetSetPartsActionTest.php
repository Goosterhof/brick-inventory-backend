<?php

declare(strict_types=1);

use App\Actions\GetSetPartsAction;
use App\Contracts\LegoDataServiceInterface;
use App\Models\Set;

describe('GetSetPartsAction', function (): void {
    it('should fetch set parts from the rebrickable service', function (): void {
        // arrange
        $expectedSet = Mockery::mock(Set::class);

        $service = Mockery::mock(LegoDataServiceInterface::class);
        $service->shouldReceive('getSetParts')
            ->with('75192-1')
            ->once()
            ->andReturn($expectedSet);

        $action = new GetSetPartsAction($service);

        // act
        $result = $action->execute('75192-1');

        // assert
        expect($result)->toBe($expectedSet);
    });
});

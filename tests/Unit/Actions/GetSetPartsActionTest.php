<?php

declare(strict_types=1);

use App\Actions\GetSetPartsAction;
use App\Contracts\LegoDataServiceInterface;
use App\DataTransferObjects\SetData;
use App\DataTransferObjects\SetPartsResultData;

describe('GetSetPartsAction', function (): void {
    it('should fetch set parts from the rebrickable service', function (): void {
        // arrange
        $expectedResult = new SetPartsResultData(
            set: new SetData(
                setNum: '75192-1',
                name: 'Millennium Falcon',
                year: 2017,
                theme: 158,
                numParts: 7541,
                imageUrl: 'https://example.com/falcon.jpg',
            ),
            parts: [],
        );

        $service = Mockery::mock(LegoDataServiceInterface::class);
        $service->shouldReceive('getSetParts')
            ->with('75192-1')
            ->once()
            ->andReturn($expectedResult);

        $action = new GetSetPartsAction($service);

        // act
        $result = $action->execute('75192-1');

        // assert
        expect($result)->toBe($expectedResult);
    });
});

<?php

declare(strict_types=1);

use App\Actions\BrickIdentification\IdentifyBrickAction;
use App\Contracts\BrickIdentification\IdentifyBrickInterface;
use App\Contracts\BrickIdentificationServiceInterface;
use App\Data\Brickognize\BrickognizePredictionData;
use App\Exceptions\BrickognizeApiException;
use App\Exceptions\PartNotFoundException;
use App\Models\Part;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;

/**
 * Create a test double for IdentifyBrickInterface.
 */
function createIdentifyBrickData(UploadedFile $uploadedFile): IdentifyBrickInterface
{
    return new readonly class($uploadedFile) implements IdentifyBrickInterface
    {
        public function __construct(
            public UploadedFile $image,
        ) {}
    };
}

describe('IdentifyBrickAction', function (): void {
    it('should return part when identification succeeds and part exists in database', function (): void {
        // arrange
        $image = UploadedFile::fake()->image('brick.jpg');
        $data = createIdentifyBrickData($image);

        $predictions = [
            new BrickognizePredictionData(
                id: '3001',
                name: 'Brick 2 x 4',
                type: 'part',
                imageUrl: 'https://example.com/3001.jpg',
                score: 0.95,
            ),
        ];

        $brickIdentificationService = Mockery::mock(BrickIdentificationServiceInterface::class);
        $brickIdentificationService->shouldReceive('identifyBrick')
            ->with($image)
            ->once()
            ->andReturn($predictions);

        $existingPart = Mockery::mock(Part::class)->makePartial();
        $existingPart->id = 1;
        $existingPart->part_num = '3001';
        $existingPart->name = 'Brick 2 x 4';

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')
            ->with('part_num', '3001')
            ->once()
            ->andReturnSelf();
        $queryBuilder->shouldReceive('first')
            ->once()
            ->andReturn($existingPart);

        $part = Mockery::mock(Part::class);
        $part->shouldReceive('newQuery')
            ->once()
            ->andReturn($queryBuilder);

        $action = new IdentifyBrickAction($brickIdentificationService, $part);

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($existingPart);
    });

    it('should select highest scoring part prediction', function (): void {
        // arrange
        $image = UploadedFile::fake()->image('brick.jpg');
        $data = createIdentifyBrickData($image);

        $predictions = [
            new BrickognizePredictionData(
                id: '3002',
                name: 'Brick 2 x 3',
                type: 'part',
                imageUrl: null,
                score: 0.72,
            ),
            new BrickognizePredictionData(
                id: '3001',
                name: 'Brick 2 x 4',
                type: 'part',
                imageUrl: null,
                score: 0.95,
            ),
            new BrickognizePredictionData(
                id: '3003',
                name: 'Brick 2 x 2',
                type: 'part',
                imageUrl: null,
                score: 0.81,
            ),
        ];

        $brickIdentificationService = Mockery::mock(BrickIdentificationServiceInterface::class);
        $brickIdentificationService->shouldReceive('identifyBrick')
            ->with($image)
            ->once()
            ->andReturn($predictions);

        $existingPart = Mockery::mock(Part::class)->makePartial();
        $existingPart->id = 1;
        $existingPart->part_num = '3001';

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')
            ->with('part_num', '3001') // Should use the highest scoring part
            ->once()
            ->andReturnSelf();
        $queryBuilder->shouldReceive('first')
            ->once()
            ->andReturn($existingPart);

        $part = Mockery::mock(Part::class);
        $part->shouldReceive('newQuery')
            ->once()
            ->andReturn($queryBuilder);

        $action = new IdentifyBrickAction($brickIdentificationService, $part);

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($existingPart);
    });

    it('should filter out non-part predictions', function (): void {
        // arrange
        $image = UploadedFile::fake()->image('brick.jpg');
        $data = createIdentifyBrickData($image);

        $predictions = [
            new BrickognizePredictionData(
                id: 'sw0001',
                name: 'Battle Droid',
                type: 'minifig',
                imageUrl: null,
                score: 0.98, // Higher score but it's a minifig
            ),
            new BrickognizePredictionData(
                id: '3001',
                name: 'Brick 2 x 4',
                type: 'part',
                imageUrl: null,
                score: 0.75,
            ),
        ];

        $brickIdentificationService = Mockery::mock(BrickIdentificationServiceInterface::class);
        $brickIdentificationService->shouldReceive('identifyBrick')
            ->with($image)
            ->once()
            ->andReturn($predictions);

        $existingPart = Mockery::mock(Part::class)->makePartial();
        $existingPart->id = 1;
        $existingPart->part_num = '3001';

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')
            ->with('part_num', '3001') // Should use the part, not the minifig
            ->once()
            ->andReturnSelf();
        $queryBuilder->shouldReceive('first')
            ->once()
            ->andReturn($existingPart);

        $part = Mockery::mock(Part::class);
        $part->shouldReceive('newQuery')
            ->once()
            ->andReturn($queryBuilder);

        $action = new IdentifyBrickAction($brickIdentificationService, $part);

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($existingPart);
    });

    it('should throw BrickognizeApiException when no part predictions found', function (): void {
        // arrange
        $image = UploadedFile::fake()->image('brick.jpg');
        $data = createIdentifyBrickData($image);

        $predictions = [
            new BrickognizePredictionData(
                id: 'sw0001',
                name: 'Battle Droid',
                type: 'minifig',
                imageUrl: null,
                score: 0.98,
            ),
        ];

        $brickIdentificationService = Mockery::mock(BrickIdentificationServiceInterface::class);
        $brickIdentificationService->shouldReceive('identifyBrick')
            ->with($image)
            ->once()
            ->andReturn($predictions);

        $part = Mockery::mock(Part::class);
        $part->shouldNotReceive('newQuery');

        $action = new IdentifyBrickAction($brickIdentificationService, $part);

        // act & assert
        expect(fn (): Part => $action->execute($data))->toThrow(BrickognizeApiException::class);
    });

    it('should throw BrickognizeApiException when API returns empty predictions', function (): void {
        // arrange
        $image = UploadedFile::fake()->image('brick.jpg');
        $data = createIdentifyBrickData($image);

        $brickIdentificationService = Mockery::mock(BrickIdentificationServiceInterface::class);
        $brickIdentificationService->shouldReceive('identifyBrick')
            ->with($image)
            ->once()
            ->andReturn([]);

        $part = Mockery::mock(Part::class);
        $part->shouldNotReceive('newQuery');

        $action = new IdentifyBrickAction($brickIdentificationService, $part);

        // act & assert
        expect(fn (): Part => $action->execute($data))->toThrow(BrickognizeApiException::class);
    });

    it('should throw PartNotFoundException when part does not exist in database', function (): void {
        // arrange
        $image = UploadedFile::fake()->image('brick.jpg');
        $data = createIdentifyBrickData($image);

        $predictions = [
            new BrickognizePredictionData(
                id: '99999',
                name: 'Unknown Part',
                type: 'part',
                imageUrl: null,
                score: 0.95,
            ),
        ];

        $brickIdentificationService = Mockery::mock(BrickIdentificationServiceInterface::class);
        $brickIdentificationService->shouldReceive('identifyBrick')
            ->with($image)
            ->once()
            ->andReturn($predictions);

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')
            ->with('part_num', '99999')
            ->once()
            ->andReturnSelf();
        $queryBuilder->shouldReceive('first')
            ->once()
            ->andReturn(null);

        $part = Mockery::mock(Part::class);
        $part->shouldReceive('newQuery')
            ->once()
            ->andReturn($queryBuilder);

        $action = new IdentifyBrickAction($brickIdentificationService, $part);

        // act & assert
        expect(fn (): Part => $action->execute($data))->toThrow(PartNotFoundException::class);
    });
});

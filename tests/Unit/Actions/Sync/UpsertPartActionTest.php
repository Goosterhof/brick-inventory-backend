<?php

declare(strict_types=1);

use App\Actions\Sync\UpsertPartAction;
use App\Data\Lego\LegoPartData;
use App\Models\Part;
use Illuminate\Database\Eloquent\Builder;

describe('UpsertPartAction', function (): void {
    it('should create a new part when it does not exist', function (): void {
        // arrange
        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->with('part_num', '3001')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('first')->once()->andReturn(null);

        $newPartSavedValues = [];
        $newPart = Mockery::mock(Part::class);
        $newPart->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$newPartSavedValues): void {
            $newPartSavedValues[$key] = $value;
        });
        $newPart->allows('getAttribute')->andReturnUsing(function ($key) use (&$newPartSavedValues): mixed {
            return $newPartSavedValues[$key] ?? null;
        });
        $newPart->shouldReceive('save')->once();

        $part = Mockery::mock(Part::class);
        $part->shouldReceive('newQuery')->once()->andReturn($queryBuilder);
        $part->shouldReceive('newInstance')->once()->andReturn($newPart);

        $action = new UpsertPartAction($part);

        $data = new LegoPartData(
            partNum: '3001',
            name: 'Brick 2 x 4',
            categoryId: 11,
            imageUrl: 'https://example.com/3001.jpg',
        );

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($newPart);
        expect($newPartSavedValues['part_num'])->toBe('3001');
        expect($newPartSavedValues['name'])->toBe('Brick 2 x 4');
        expect($newPartSavedValues['category'])->toBe('11');
        expect($newPartSavedValues['image_url'])->toBe('https://example.com/3001.jpg');
    });

    it('should update an existing part when it exists', function (): void {
        // arrange
        $existingSavedValues = ['id' => 1, 'part_num' => '3001'];
        $existingPart = Mockery::mock(Part::class);
        $existingPart->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$existingSavedValues): void {
            $existingSavedValues[$key] = $value;
        });
        $existingPart->allows('getAttribute')->andReturnUsing(function ($key) use (&$existingSavedValues): mixed {
            return $existingSavedValues[$key] ?? null;
        });
        $existingPart->shouldReceive('save')->once();

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->with('part_num', '3001')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('first')->once()->andReturn($existingPart);

        $part = Mockery::mock(Part::class);
        $part->shouldReceive('newQuery')->once()->andReturn($queryBuilder);

        $action = new UpsertPartAction($part);

        $data = new LegoPartData(
            partNum: '3001',
            name: 'Updated Brick 2 x 4',
            categoryId: 12,
            imageUrl: 'https://example.com/updated.jpg',
        );

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($existingPart);
        expect($existingSavedValues['name'])->toBe('Updated Brick 2 x 4');
        expect($existingSavedValues['category'])->toBe('12');
        expect($existingSavedValues['image_url'])->toBe('https://example.com/updated.jpg');
    });

    it('should handle null part_cat_id and part_img_url', function (): void {
        // arrange
        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->andReturnSelf();
        $queryBuilder->shouldReceive('first')->andReturn(null);

        $newPartSavedValues = [];
        $newPart = Mockery::mock(Part::class);
        $newPart->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$newPartSavedValues): void {
            $newPartSavedValues[$key] = $value;
        });
        $newPart->allows('getAttribute')->andReturnUsing(function ($key) use (&$newPartSavedValues): mixed {
            return $newPartSavedValues[$key] ?? null;
        });
        $newPart->shouldReceive('save')->once();

        $part = Mockery::mock(Part::class);
        $part->shouldReceive('newQuery')->andReturn($queryBuilder);
        $part->shouldReceive('newInstance')->andReturn($newPart);

        $action = new UpsertPartAction($part);

        $data = new LegoPartData(
            partNum: '3002',
            name: 'Brick 2 x 3',
            categoryId: null,
            imageUrl: null,
        );

        // act
        $result = $action->execute($data);

        // assert
        expect($newPartSavedValues['category'])->toBeNull();
        expect($newPartSavedValues['image_url'])->toBeNull();
    });
});

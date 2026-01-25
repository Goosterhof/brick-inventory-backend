<?php

declare(strict_types=1);

use App\Actions\Sync\UpsertPartAction;
use App\Models\Part;
use Illuminate\Database\Eloquent\Builder;

describe('UpsertPartAction', function (): void {
    it('should create a new part when it does not exist', function (): void {
        // arrange
        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->with('part_num', '3001')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('first')->once()->andReturn(null);

        $newPart = Mockery::mock(Part::class)->makePartial();
        $newPart->shouldReceive('save')->once();

        $part = Mockery::mock(Part::class);
        $part->shouldReceive('newQuery')->once()->andReturn($queryBuilder);
        $part->shouldReceive('newInstance')->once()->andReturn($newPart);

        $action = new UpsertPartAction($part);

        $data = [
            'part_num' => '3001',
            'name' => 'Brick 2 x 4',
            'part_cat_id' => 11,
            'part_img_url' => 'https://example.com/3001.jpg',
        ];

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($newPart);
        expect($result->part_num)->toBe('3001');
        expect($result->name)->toBe('Brick 2 x 4');
        expect($result->category)->toBe('11');
        expect($result->image_url)->toBe('https://example.com/3001.jpg');
    });

    it('should update an existing part when it exists', function (): void {
        // arrange
        $existingPart = Mockery::mock(Part::class)->makePartial();
        $existingPart->id = 1;
        $existingPart->part_num = '3001';
        $existingPart->shouldReceive('save')->once();

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->with('part_num', '3001')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('first')->once()->andReturn($existingPart);

        $part = Mockery::mock(Part::class);
        $part->shouldReceive('newQuery')->once()->andReturn($queryBuilder);

        $action = new UpsertPartAction($part);

        $data = [
            'part_num' => '3001',
            'name' => 'Updated Brick 2 x 4',
            'part_cat_id' => 12,
            'part_img_url' => 'https://example.com/updated.jpg',
        ];

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($existingPart);
        expect($result->name)->toBe('Updated Brick 2 x 4');
        expect($result->category)->toBe('12');
        expect($result->image_url)->toBe('https://example.com/updated.jpg');
    });

    it('should handle null part_cat_id and part_img_url', function (): void {
        // arrange
        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->andReturnSelf();
        $queryBuilder->shouldReceive('first')->andReturn(null);

        $newPart = Mockery::mock(Part::class)->makePartial();
        $newPart->shouldReceive('save')->once();

        $part = Mockery::mock(Part::class);
        $part->shouldReceive('newQuery')->andReturn($queryBuilder);
        $part->shouldReceive('newInstance')->andReturn($newPart);

        $action = new UpsertPartAction($part);

        $data = [
            'part_num' => '3002',
            'name' => 'Brick 2 x 3',
            'part_cat_id' => null,
            'part_img_url' => null,
        ];

        // act
        $result = $action->execute($data);

        // assert
        expect($result->category)->toBeNull();
        expect($result->image_url)->toBeNull();
    });
});

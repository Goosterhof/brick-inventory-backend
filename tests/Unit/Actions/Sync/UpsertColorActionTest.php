<?php

declare(strict_types=1);

use App\Actions\Sync\UpsertColorAction;
use App\Data\Lego\LegoColorData;
use App\Models\Color;
use Illuminate\Database\Eloquent\Builder;

describe('UpsertColorAction', function (): void {
    it('should create a new color when it does not exist', function (): void {
        // arrange
        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->with('rebrickable_id', 1)->once()->andReturnSelf();
        $queryBuilder->shouldReceive('first')->once()->andReturn(null);

        $newColor = Mockery::mock(Color::class)->makePartial();
        $newColor->shouldReceive('save')->once();

        $color = Mockery::mock(Color::class);
        $color->shouldReceive('newQuery')->once()->andReturn($queryBuilder);
        $color->shouldReceive('newInstance')->once()->andReturn($newColor);

        $action = new UpsertColorAction($color);

        $data = new LegoColorData(
            id: 1,
            name: 'White',
            rgb: 'FFFFFF',
            isTransparent: false,
        );

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($newColor);
        expect($result->rebrickable_id)->toBe(1);
        expect($result->name)->toBe('White');
        expect($result->rgb)->toBe('FFFFFF');
        expect($result->is_transparent)->toBeFalse();
    });

    it('should update an existing color when it exists', function (): void {
        // arrange
        $existingColor = Mockery::mock(Color::class)->makePartial();
        $existingColor->id = 1;
        $existingColor->rebrickable_id = 1;
        $existingColor->shouldReceive('save')->once();

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->with('rebrickable_id', 1)->once()->andReturnSelf();
        $queryBuilder->shouldReceive('first')->once()->andReturn($existingColor);

        $color = Mockery::mock(Color::class);
        $color->shouldReceive('newQuery')->once()->andReturn($queryBuilder);

        $action = new UpsertColorAction($color);

        $data = new LegoColorData(
            id: 1,
            name: 'Updated White',
            rgb: 'FFFFF0',
            isTransparent: false,
        );

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBe($existingColor);
        expect($result->name)->toBe('Updated White');
        expect($result->rgb)->toBe('FFFFF0');
    });

    it('should handle transparent colors', function (): void {
        // arrange
        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('where')->andReturnSelf();
        $queryBuilder->shouldReceive('first')->andReturn(null);

        $newColor = Mockery::mock(Color::class)->makePartial();
        $newColor->shouldReceive('save')->once();

        $color = Mockery::mock(Color::class);
        $color->shouldReceive('newQuery')->andReturn($queryBuilder);
        $color->shouldReceive('newInstance')->andReturn($newColor);

        $action = new UpsertColorAction($color);

        $data = new LegoColorData(
            id: 41,
            name: 'Trans-Red',
            rgb: 'FF0000',
            isTransparent: true,
        );

        // act
        $result = $action->execute($data);

        // assert
        expect($result->name)->toBe('Trans-Red');
        expect($result->is_transparent)->toBeTrue();
    });
});

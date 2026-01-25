<?php

declare(strict_types=1);

use App\Http\Resources\ColorResourceData;
use App\Http\Resources\PartResourceData;
use App\Http\Resources\SetPartResourceData;
use App\Models\Color;
use App\Models\Part;
use App\Models\SetPart;

describe('SetPartResourceData', function (): void {
    it('should convert set part model to resource data', function (): void {
        // arrange
        $part = Mockery::mock(Part::class)->makePartial();
        $part->id = 1;
        $part->part_num = '3001';
        $part->name = 'Brick 2 x 4';
        $part->category = 'Bricks';
        $part->image_url = 'https://example.com/3001.jpg';

        $color = Mockery::mock(Color::class)->makePartial();
        $color->id = 1;
        $color->rebrickable_id = 15;
        $color->name = 'White';
        $color->rgb = 'FFFFFF';
        $color->is_transparent = false;

        $setPart = Mockery::mock(SetPart::class)->makePartial();
        $setPart->id = 1;
        $setPart->quantity = 10;
        $setPart->is_spare = false;
        $setPart->element_id = '300101';
        $setPart->part = $part;
        $setPart->color = $color;

        // act
        $resource = SetPartResourceData::from($setPart);

        // assert
        expect($resource)->toBeInstanceOf(SetPartResourceData::class)
            ->and($resource->id)->toBe(1)
            ->and($resource->quantity)->toBe(10)
            ->and($resource->is_spare)->toBeFalse()
            ->and($resource->element_id)->toBe('300101')
            ->and($resource->part)->toBeInstanceOf(PartResourceData::class)
            ->and($resource->part->part_num)->toBe('3001')
            ->and($resource->color)->toBeInstanceOf(ColorResourceData::class)
            ->and($resource->color->name)->toBe('White');
    });

    it('should handle spare parts', function (): void {
        // arrange
        $part = Mockery::mock(Part::class)->makePartial();
        $part->id = 2;
        $part->part_num = '3002';
        $part->name = 'Brick 2 x 3';
        $part->category = 'Bricks';
        $part->image_url = null;

        $color = Mockery::mock(Color::class)->makePartial();
        $color->id = 2;
        $color->rebrickable_id = 0;
        $color->name = 'Black';
        $color->rgb = '000000';
        $color->is_transparent = false;

        $setPart = Mockery::mock(SetPart::class)->makePartial();
        $setPart->id = 2;
        $setPart->quantity = 3;
        $setPart->is_spare = true;
        $setPart->element_id = null;
        $setPart->part = $part;
        $setPart->color = $color;

        // act
        $resource = SetPartResourceData::from($setPart);

        // assert
        expect($resource->is_spare)->toBeTrue()
            ->and($resource->element_id)->toBeNull();
    });

    it('should convert to array format', function (): void {
        // arrange
        $part = Mockery::mock(Part::class)->makePartial();
        $part->id = 1;
        $part->part_num = '3001';
        $part->name = 'Brick 2 x 4';
        $part->category = 'Bricks';
        $part->image_url = 'https://example.com/3001.jpg';

        $color = Mockery::mock(Color::class)->makePartial();
        $color->id = 1;
        $color->rebrickable_id = 15;
        $color->name = 'White';
        $color->rgb = 'FFFFFF';
        $color->is_transparent = false;

        $setPart = Mockery::mock(SetPart::class)->makePartial();
        $setPart->id = 1;
        $setPart->quantity = 10;
        $setPart->is_spare = false;
        $setPart->element_id = '300101';
        $setPart->part = $part;
        $setPart->color = $color;

        // act
        $resource = SetPartResourceData::from($setPart);
        $array = $resource->toArray();

        // assert
        expect($array)->toBeArray()
            ->and($array['id'])->toBe(1)
            ->and($array['quantity'])->toBe(10)
            ->and($array['is_spare'])->toBeFalse()
            ->and($array['element_id'])->toBe('300101')
            ->and($array['part'])->toBeArray()
            ->and($array['part']['part_num'])->toBe('3001')
            ->and($array['color'])->toBeArray()
            ->and($array['color']['name'])->toBe('White');
    });
});

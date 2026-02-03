<?php

declare(strict_types=1);

use App\Http\Resources\SetPartResourceData;
use App\Models\SetPart;

describe('SetPartResourceData', function (): void {
    it('should convert set part model to resource data', function (): void {
        // arrange
        $setPart = Mockery::mock(SetPart::class);
        $setPart->allows('getAttribute')->with('id')->andReturn(1);
        $setPart->allows('getAttribute')->with('part_id')->andReturn(10);
        $setPart->allows('getAttribute')->with('color_id')->andReturn(5);
        $setPart->allows('getAttribute')->with('quantity')->andReturn(10);
        $setPart->allows('getAttribute')->with('is_spare')->andReturn(false);
        $setPart->allows('getAttribute')->with('element_id')->andReturn('300101');

        // act
        $resource = SetPartResourceData::from($setPart);

        // assert
        expect($resource)->toBeInstanceOf(SetPartResourceData::class)
            ->and($resource->id)->toBe(1)
            ->and($resource->part_id)->toBe(10)
            ->and($resource->color_id)->toBe(5)
            ->and($resource->quantity)->toBe(10)
            ->and($resource->is_spare)->toBeFalse()
            ->and($resource->element_id)->toBe('300101');
    });

    it('should handle spare parts', function (): void {
        // arrange
        $setPart = Mockery::mock(SetPart::class);
        $setPart->allows('getAttribute')->with('id')->andReturn(2);
        $setPart->allows('getAttribute')->with('part_id')->andReturn(20);
        $setPart->allows('getAttribute')->with('color_id')->andReturn(15);
        $setPart->allows('getAttribute')->with('quantity')->andReturn(3);
        $setPart->allows('getAttribute')->with('is_spare')->andReturn(true);
        $setPart->allows('getAttribute')->with('element_id')->andReturn(null);

        // act
        $resource = SetPartResourceData::from($setPart);

        // assert
        expect($resource->is_spare)->toBeTrue()
            ->and($resource->element_id)->toBeNull();
    });

    it('should convert to array format', function (): void {
        // arrange
        $setPart = Mockery::mock(SetPart::class);
        $setPart->allows('getAttribute')->with('id')->andReturn(1);
        $setPart->allows('getAttribute')->with('part_id')->andReturn(10);
        $setPart->allows('getAttribute')->with('color_id')->andReturn(5);
        $setPart->allows('getAttribute')->with('quantity')->andReturn(10);
        $setPart->allows('getAttribute')->with('is_spare')->andReturn(false);
        $setPart->allows('getAttribute')->with('element_id')->andReturn('300101');

        // act
        $resource = SetPartResourceData::from($setPart);
        $array = $resource->toArray();

        // assert
        expect($array)->toBeArray()
            ->and($array['id'])->toBe(1)
            ->and($array['part_id'])->toBe(10)
            ->and($array['color_id'])->toBe(5)
            ->and($array['quantity'])->toBe(10)
            ->and($array['is_spare'])->toBeFalse()
            ->and($array['element_id'])->toBe('300101');
    });
});

<?php

declare(strict_types=1);

use App\Http\Resources\SetPartResourceData;
use App\Models\SetPart;

describe('SetPartResourceData', function (): void {
    it('should convert set part model to resource data', function (): void {
        // arrange
        $setPart = Mockery::mock(SetPart::class)->makePartial();
        $setPart->id = 1;
        $setPart->part_id = 10;
        $setPart->color_id = 5;
        $setPart->quantity = 10;
        $setPart->is_spare = false;
        $setPart->element_id = '300101';

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
        $setPart = Mockery::mock(SetPart::class)->makePartial();
        $setPart->id = 2;
        $setPart->part_id = 20;
        $setPart->color_id = 15;
        $setPart->quantity = 3;
        $setPart->is_spare = true;
        $setPart->element_id = null;

        // act
        $resource = SetPartResourceData::from($setPart);

        // assert
        expect($resource->is_spare)->toBeTrue()
            ->and($resource->element_id)->toBeNull();
    });

    it('should convert to array format', function (): void {
        // arrange
        $setPart = Mockery::mock(SetPart::class)->makePartial();
        $setPart->id = 1;
        $setPart->part_id = 10;
        $setPart->color_id = 5;
        $setPart->quantity = 10;
        $setPart->is_spare = false;
        $setPart->element_id = '300101';

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

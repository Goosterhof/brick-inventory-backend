<?php

declare(strict_types=1);

use App\Http\Resources\StorageOptionPartResourceData;
use App\Models\StorageOptionPart;

describe('StorageOptionPartResourceData', function (): void {
    it('should convert storage option part model to resource data', function (): void {
        // arrange
        $part = Mockery::mock(StorageOptionPart::class);
        $part->allows('getAttribute')->with('id')->andReturn(1);
        $part->allows('getAttribute')->with('storage_option_id')->andReturn(10);
        $part->allows('getAttribute')->with('part_id')->andReturn(20);
        $part->allows('getAttribute')->with('color_id')->andReturn(5);
        $part->allows('getAttribute')->with('quantity')->andReturn(15);

        // act
        $resource = StorageOptionPartResourceData::from($part);

        // assert
        expect($resource)->toBeInstanceOf(StorageOptionPartResourceData::class)
            ->and($resource->id)->toBe(1)
            ->and($resource->storage_option_id)->toBe(10)
            ->and($resource->part_id)->toBe(20)
            ->and($resource->color_id)->toBe(5)
            ->and($resource->quantity)->toBe(15);
    });

    it('should handle nullable color_id', function (): void {
        // arrange
        $part = Mockery::mock(StorageOptionPart::class);
        $part->allows('getAttribute')->with('id')->andReturn(2);
        $part->allows('getAttribute')->with('storage_option_id')->andReturn(10);
        $part->allows('getAttribute')->with('part_id')->andReturn(30);
        $part->allows('getAttribute')->with('color_id')->andReturn(null);
        $part->allows('getAttribute')->with('quantity')->andReturn(8);

        // act
        $resource = StorageOptionPartResourceData::from($part);

        // assert
        expect($resource->color_id)->toBeNull();
    });

    it('should convert to array format', function (): void {
        // arrange
        $part = Mockery::mock(StorageOptionPart::class);
        $part->allows('getAttribute')->with('id')->andReturn(1);
        $part->allows('getAttribute')->with('storage_option_id')->andReturn(10);
        $part->allows('getAttribute')->with('part_id')->andReturn(20);
        $part->allows('getAttribute')->with('color_id')->andReturn(5);
        $part->allows('getAttribute')->with('quantity')->andReturn(15);

        // act
        $array = StorageOptionPartResourceData::from($part)->toArray();

        // assert
        expect($array)->toBeArray()
            ->and($array['id'])->toBe(1)
            ->and($array['storage_option_id'])->toBe(10)
            ->and($array['part_id'])->toBe(20)
            ->and($array['color_id'])->toBe(5)
            ->and($array['quantity'])->toBe(15);
    });
});

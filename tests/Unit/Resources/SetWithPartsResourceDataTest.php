<?php

declare(strict_types=1);

use App\Http\Resources\SetPartResourceData;
use App\Http\Resources\SetWithPartsResourceData;
use App\Models\Set;
use App\Models\SetPart;
use Illuminate\Support\Collection;

describe('SetWithPartsResourceData', function (): void {
    it('should convert set with parts to resource data', function (): void {
        // arrange
        $setPart = Mockery::mock(SetPart::class);
        $setPart->allows('getAttribute')->with('id')->andReturn(1);
        $setPart->allows('getAttribute')->with('part_id')->andReturn(10);
        $setPart->allows('getAttribute')->with('color_id')->andReturn(5);
        $setPart->allows('getAttribute')->with('quantity')->andReturn(10);
        $setPart->allows('getAttribute')->with('is_spare')->andReturn(false);
        $setPart->allows('getAttribute')->with('element_id')->andReturn('300101');

        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(1);
        $set->allows('getAttribute')->with('set_num')->andReturn('75192-1');
        $set->allows('getAttribute')->with('name')->andReturn('Millennium Falcon');
        $set->allows('getAttribute')->with('year')->andReturn(2017);
        $set->allows('getAttribute')->with('theme')->andReturn('Star Wars');
        $set->allows('getAttribute')->with('num_parts')->andReturn(7541);
        $set->allows('getAttribute')->with('image_url')->andReturn('https://example.com/falcon.jpg');
        $set->allows('getAttribute')->with('setParts')->andReturn(new Collection([$setPart]));
        $set->shouldReceive('loadMissing')->andReturnSelf();
        $set->shouldReceive('relationLoaded')->with('setParts')->andReturnTrue();

        // act
        $resource = SetWithPartsResourceData::from($set);

        // assert
        expect($resource)->toBeInstanceOf(SetWithPartsResourceData::class)
            ->and($resource->id)->toBe(1)
            ->and($resource->set_num)->toBe('75192-1')
            ->and($resource->name)->toBe('Millennium Falcon')
            ->and($resource->year)->toBe(2017)
            ->and($resource->theme)->toBe('Star Wars')
            ->and($resource->num_parts)->toBe(7541)
            ->and($resource->image_url)->toBe('https://example.com/falcon.jpg')
            ->and($resource->parts)->toBeArray()
            ->and($resource->parts)->toHaveCount(1)
            ->and($resource->parts[0])->toBeInstanceOf(SetPartResourceData::class)
            ->and($resource->parts[0]->quantity)->toBe(10);
    });

    it('should handle empty parts', function (): void {
        // arrange
        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(1);
        $set->allows('getAttribute')->with('set_num')->andReturn('10281-1');
        $set->allows('getAttribute')->with('name')->andReturn('Bonsai Tree');
        $set->allows('getAttribute')->with('year')->andReturn(2021);
        $set->allows('getAttribute')->with('theme')->andReturn(null);
        $set->allows('getAttribute')->with('num_parts')->andReturn(878);
        $set->allows('getAttribute')->with('image_url')->andReturn(null);
        $set->allows('getAttribute')->with('setParts')->andReturn(new Collection([]));
        $set->shouldReceive('loadMissing')->andReturnSelf();
        $set->shouldReceive('relationLoaded')->with('setParts')->andReturnTrue();

        // act
        $resource = SetWithPartsResourceData::from($set);

        // assert
        expect($resource->set_num)->toBe('10281-1')
            ->and($resource->parts)->toBeArray()
            ->and($resource->parts)->toBeEmpty();
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

        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(1);
        $set->allows('getAttribute')->with('set_num')->andReturn('75192-1');
        $set->allows('getAttribute')->with('name')->andReturn('Millennium Falcon');
        $set->allows('getAttribute')->with('year')->andReturn(2017);
        $set->allows('getAttribute')->with('theme')->andReturn('Star Wars');
        $set->allows('getAttribute')->with('num_parts')->andReturn(7541);
        $set->allows('getAttribute')->with('image_url')->andReturn('https://example.com/falcon.jpg');
        $set->allows('getAttribute')->with('setParts')->andReturn(new Collection([$setPart]));
        $set->shouldReceive('loadMissing')->andReturnSelf();
        $set->shouldReceive('relationLoaded')->with('setParts')->andReturnTrue();

        // act
        $resource = SetWithPartsResourceData::from($set);
        $array = $resource->toArray();

        // assert
        expect($array)->toBeArray()
            ->and($array['id'])->toBe(1)
            ->and($array['set_num'])->toBe('75192-1')
            ->and($array['name'])->toBe('Millennium Falcon')
            ->and($array['parts'])->toBeArray()
            ->and($array['parts'])->toHaveCount(1)
            ->and($array['parts'][0]['quantity'])->toBe(10);
    });

    it('should handle multiple parts', function (): void {
        // arrange
        $setPart1 = Mockery::mock(SetPart::class);
        $setPart1->allows('getAttribute')->with('id')->andReturn(1);
        $setPart1->allows('getAttribute')->with('part_id')->andReturn(10);
        $setPart1->allows('getAttribute')->with('color_id')->andReturn(5);
        $setPart1->allows('getAttribute')->with('quantity')->andReturn(5);
        $setPart1->allows('getAttribute')->with('is_spare')->andReturn(false);
        $setPart1->allows('getAttribute')->with('element_id')->andReturn(null);

        $setPart2 = Mockery::mock(SetPart::class);
        $setPart2->allows('getAttribute')->with('id')->andReturn(2);
        $setPart2->allows('getAttribute')->with('part_id')->andReturn(20);
        $setPart2->allows('getAttribute')->with('color_id')->andReturn(15);
        $setPart2->allows('getAttribute')->with('quantity')->andReturn(3);
        $setPart2->allows('getAttribute')->with('is_spare')->andReturn(true);
        $setPart2->allows('getAttribute')->with('element_id')->andReturn('300226');

        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(1);
        $set->allows('getAttribute')->with('set_num')->andReturn('10179-1');
        $set->allows('getAttribute')->with('name')->andReturn('Ultimate Collector Millennium Falcon');
        $set->allows('getAttribute')->with('year')->andReturn(2007);
        $set->allows('getAttribute')->with('theme')->andReturn('Star Wars');
        $set->allows('getAttribute')->with('num_parts')->andReturn(5195);
        $set->allows('getAttribute')->with('image_url')->andReturn(null);
        $set->allows('getAttribute')->with('setParts')->andReturn(new Collection([$setPart1, $setPart2]));
        $set->shouldReceive('loadMissing')->andReturnSelf();
        $set->shouldReceive('relationLoaded')->with('setParts')->andReturnTrue();

        // act
        $resource = SetWithPartsResourceData::from($set);

        // assert
        expect($resource->parts)->toHaveCount(2)
            ->and($resource->parts[0]->part_id)->toBe(10)
            ->and($resource->parts[1]->part_id)->toBe(20)
            ->and($resource->parts[1]->is_spare)->toBeTrue();
    });
});

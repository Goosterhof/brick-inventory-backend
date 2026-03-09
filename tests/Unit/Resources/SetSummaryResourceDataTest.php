<?php

declare(strict_types=1);

use App\Http\Resources\SetSummaryResourceData;
use App\Models\Set;

describe('SetSummaryResourceData', function (): void {
    it('should convert set model to summary resource data', function (): void {
        // arrange
        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(1);
        $set->allows('getAttribute')->with('set_num')->andReturn('75192-1');
        $set->allows('getAttribute')->with('name')->andReturn('Millennium Falcon');
        $set->allows('getAttribute')->with('year')->andReturn(2017);
        $set->allows('getAttribute')->with('theme')->andReturn('Star Wars');
        $set->allows('getAttribute')->with('num_parts')->andReturn(7541);
        $set->allows('getAttribute')->with('image_url')->andReturn('https://example.com/falcon.jpg');

        // act
        $resource = SetSummaryResourceData::from($set);

        // assert
        expect($resource)->toBeInstanceOf(SetSummaryResourceData::class)
            ->and($resource->id)->toBe(1)
            ->and($resource->set_num)->toBe('75192-1')
            ->and($resource->name)->toBe('Millennium Falcon')
            ->and($resource->year)->toBe(2017)
            ->and($resource->theme)->toBe('Star Wars')
            ->and($resource->num_parts)->toBe(7541)
            ->and($resource->image_url)->toBe('https://example.com/falcon.jpg');
    });

    it('should handle nullable year and theme', function (): void {
        // arrange
        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(2);
        $set->allows('getAttribute')->with('set_num')->andReturn('10281-1');
        $set->allows('getAttribute')->with('name')->andReturn('Bonsai Tree');
        $set->allows('getAttribute')->with('year')->andReturn(null);
        $set->allows('getAttribute')->with('theme')->andReturn(null);
        $set->allows('getAttribute')->with('num_parts')->andReturn(878);
        $set->allows('getAttribute')->with('image_url')->andReturn(null);

        // act
        $resource = SetSummaryResourceData::from($set);

        // assert
        expect($resource->year)->toBeNull()
            ->and($resource->theme)->toBeNull()
            ->and($resource->image_url)->toBeNull();
    });

    it('should convert to array format', function (): void {
        // arrange
        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(1);
        $set->allows('getAttribute')->with('set_num')->andReturn('75192-1');
        $set->allows('getAttribute')->with('name')->andReturn('Millennium Falcon');
        $set->allows('getAttribute')->with('year')->andReturn(2017);
        $set->allows('getAttribute')->with('theme')->andReturn('Star Wars');
        $set->allows('getAttribute')->with('num_parts')->andReturn(7541);
        $set->allows('getAttribute')->with('image_url')->andReturn('https://example.com/falcon.jpg');

        // act
        $array = SetSummaryResourceData::from($set)->toArray();

        // assert
        expect($array)->toBeArray()
            ->and($array['id'])->toBe(1)
            ->and($array['set_num'])->toBe('75192-1')
            ->and($array['name'])->toBe('Millennium Falcon')
            ->and($array['year'])->toBe(2017)
            ->and($array['theme'])->toBe('Star Wars')
            ->and($array['num_parts'])->toBe(7541)
            ->and($array['image_url'])->toBe('https://example.com/falcon.jpg');
    });
});

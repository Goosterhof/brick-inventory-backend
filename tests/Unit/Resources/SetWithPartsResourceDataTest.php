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
        $setPart = Mockery::mock(SetPart::class)->makePartial();
        $setPart->id = 1;
        $setPart->part_id = 10;
        $setPart->color_id = 5;
        $setPart->quantity = 10;
        $setPart->is_spare = false;
        $setPart->element_id = '300101';

        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 1;
        $set->set_num = '75192-1';
        $set->name = 'Millennium Falcon';
        $set->year = 2017;
        $set->theme = 'Star Wars';
        $set->num_parts = 7541;
        $set->image_url = 'https://example.com/falcon.jpg';
        $set->setParts = new Collection([$setPart]);
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
        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 1;
        $set->set_num = '10281-1';
        $set->name = 'Bonsai Tree';
        $set->year = 2021;
        $set->theme = null;
        $set->num_parts = 878;
        $set->image_url = null;
        $set->setParts = new Collection([]);
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
        $setPart = Mockery::mock(SetPart::class)->makePartial();
        $setPart->id = 1;
        $setPart->part_id = 10;
        $setPart->color_id = 5;
        $setPart->quantity = 10;
        $setPart->is_spare = false;
        $setPart->element_id = '300101';

        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 1;
        $set->set_num = '75192-1';
        $set->name = 'Millennium Falcon';
        $set->year = 2017;
        $set->theme = 'Star Wars';
        $set->num_parts = 7541;
        $set->image_url = 'https://example.com/falcon.jpg';
        $set->setParts = new Collection([$setPart]);
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
        $setPart1 = Mockery::mock(SetPart::class)->makePartial();
        $setPart1->id = 1;
        $setPart1->part_id = 10;
        $setPart1->color_id = 5;
        $setPart1->quantity = 5;
        $setPart1->is_spare = false;
        $setPart1->element_id = null;

        $setPart2 = Mockery::mock(SetPart::class)->makePartial();
        $setPart2->id = 2;
        $setPart2->part_id = 20;
        $setPart2->color_id = 15;
        $setPart2->quantity = 3;
        $setPart2->is_spare = true;
        $setPart2->element_id = '300226';

        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 1;
        $set->set_num = '10179-1';
        $set->name = 'Ultimate Collector Millennium Falcon';
        $set->year = 2007;
        $set->theme = 'Star Wars';
        $set->num_parts = 5195;
        $set->image_url = null;
        $set->setParts = new Collection([$setPart1, $setPart2]);
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

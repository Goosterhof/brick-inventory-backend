<?php

declare(strict_types=1);

use App\Http\Resources\SetPartResourceData;
use App\Http\Resources\SetResourceData;
use App\Http\Resources\SetWithPartsResourceData;
use App\Models\Color;
use App\Models\Part;
use App\Models\Set;
use App\Models\SetPart;
use Illuminate\Support\Collection;

describe('SetWithPartsResourceData', function (): void {
    it('should convert set with parts to resource data', function (): void {
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
        $setPart->shouldReceive('loadMissing')->andReturnSelf();

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

        // act
        $resource = SetWithPartsResourceData::from($set);

        // assert
        expect($resource)->toBeInstanceOf(SetWithPartsResourceData::class)
            ->and($resource->set)->toBeInstanceOf(SetResourceData::class)
            ->and($resource->set->set_num)->toBe('75192-1')
            ->and($resource->set->name)->toBe('Millennium Falcon')
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

        // act
        $resource = SetWithPartsResourceData::from($set);

        // assert
        expect($resource->set->set_num)->toBe('10281-1')
            ->and($resource->parts)->toBeArray()
            ->and($resource->parts)->toBeEmpty();
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
        $setPart->shouldReceive('loadMissing')->andReturnSelf();

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

        // act
        $resource = SetWithPartsResourceData::from($set);
        $array = $resource->toArray();

        // assert
        expect($array)->toBeArray()
            ->and($array['set'])->toBeArray()
            ->and($array['set']['set_num'])->toBe('75192-1')
            ->and($array['parts'])->toBeArray()
            ->and($array['parts'])->toHaveCount(1)
            ->and($array['parts'][0]['quantity'])->toBe(10);
    });

    it('should handle multiple parts', function (): void {
        // arrange
        $part1 = Mockery::mock(Part::class)->makePartial();
        $part1->id = 1;
        $part1->part_num = '3001';
        $part1->name = 'Brick 2 x 4';
        $part1->category = 'Bricks';
        $part1->image_url = null;

        $part2 = Mockery::mock(Part::class)->makePartial();
        $part2->id = 2;
        $part2->part_num = '3002';
        $part2->name = 'Brick 2 x 3';
        $part2->category = 'Bricks';
        $part2->image_url = null;

        $color1 = Mockery::mock(Color::class)->makePartial();
        $color1->id = 1;
        $color1->rebrickable_id = 15;
        $color1->name = 'White';
        $color1->rgb = 'FFFFFF';
        $color1->is_transparent = false;

        $color2 = Mockery::mock(Color::class)->makePartial();
        $color2->id = 2;
        $color2->rebrickable_id = 0;
        $color2->name = 'Black';
        $color2->rgb = '000000';
        $color2->is_transparent = false;

        $setPart1 = Mockery::mock(SetPart::class)->makePartial();
        $setPart1->id = 1;
        $setPart1->quantity = 5;
        $setPart1->is_spare = false;
        $setPart1->element_id = null;
        $setPart1->part = $part1;
        $setPart1->color = $color1;
        $setPart1->shouldReceive('loadMissing')->andReturnSelf();

        $setPart2 = Mockery::mock(SetPart::class)->makePartial();
        $setPart2->id = 2;
        $setPart2->quantity = 3;
        $setPart2->is_spare = true;
        $setPart2->element_id = '300226';
        $setPart2->part = $part2;
        $setPart2->color = $color2;
        $setPart2->shouldReceive('loadMissing')->andReturnSelf();

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

        // act
        $resource = SetWithPartsResourceData::from($set);

        // assert
        expect($resource->parts)->toHaveCount(2)
            ->and($resource->parts[0]->part->part_num)->toBe('3001')
            ->and($resource->parts[1]->part->part_num)->toBe('3002')
            ->and($resource->parts[1]->is_spare)->toBeTrue();
    });
});

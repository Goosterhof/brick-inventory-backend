<?php

declare(strict_types=1);

use App\Models\Color;
use App\Models\Part;
use App\Models\Set;
use App\Models\SetPart;
use App\Services\RebrickableService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

describe('RebrickableService', function (): void {
    describe('fetchSet', function (): void {
        it('should return set data from API', function (): void {
            // arrange
            Http::fake([
                'https://rebrickable.com/api/v3/lego/sets/75192-1/' => Http::response([
                    'set_num' => '75192-1',
                    'name' => 'Millennium Falcon',
                    'year' => 2017,
                    'theme_id' => 158,
                    'num_parts' => 7541,
                    'set_img_url' => 'https://example.com/75192.jpg',
                ]),
            ]);

            $set = Mockery::mock(Set::class);
            $part = Mockery::mock(Part::class);
            $color = Mockery::mock(Color::class);
            $setPart = Mockery::mock(SetPart::class);

            $service = new RebrickableService($set, $part, $color, $setPart);

            // act
            $result = $service->fetchSet('75192-1');

            // assert
            expect($result)->toBe([
                'set_num' => '75192-1',
                'name' => 'Millennium Falcon',
                'year' => 2017,
                'theme_id' => 158,
                'num_parts' => 7541,
                'set_img_url' => 'https://example.com/75192.jpg',
            ]);

            Http::assertSent(fn ($request): bool => $request->url() === 'https://rebrickable.com/api/v3/lego/sets/75192-1/');
        });

        it('should throw RequestException when API fails', function (): void {
            // arrange
            Http::fake([
                'https://rebrickable.com/api/v3/lego/sets/invalid/' => Http::response([], 404),
            ]);

            $set = Mockery::mock(Set::class);
            $part = Mockery::mock(Part::class);
            $color = Mockery::mock(Color::class);
            $setPart = Mockery::mock(SetPart::class);

            $service = new RebrickableService($set, $part, $color, $setPart);

            // act & assert
            expect(fn (): array => $service->fetchSet('invalid'))->toThrow(RequestException::class);
        });

        it('should handle null theme_id and set_img_url', function (): void {
            // arrange
            Http::fake([
                'https://rebrickable.com/api/v3/lego/sets/10281-1/' => Http::response([
                    'set_num' => '10281-1',
                    'name' => 'Bonsai Tree',
                    'year' => 2021,
                    'theme_id' => null,
                    'num_parts' => 878,
                    'set_img_url' => null,
                ]),
            ]);

            $set = Mockery::mock(Set::class);
            $part = Mockery::mock(Part::class);
            $color = Mockery::mock(Color::class);
            $setPart = Mockery::mock(SetPart::class);

            $service = new RebrickableService($set, $part, $color, $setPart);

            // act
            $result = $service->fetchSet('10281-1');

            // assert
            expect($result['theme_id'])->toBeNull();
            expect($result['set_img_url'])->toBeNull();
        });
    });

    describe('getSetParts', function (): void {
        it('should return existing set from database when set has parts', function (): void {
            // arrange
            $setPartsRelation = Mockery::mock(HasMany::class);
            $setPartsRelation->shouldReceive('exists')->once()->andReturn(true);

            $existingSet = Mockery::mock(Set::class)->makePartial();
            $existingSet->id = 1;
            $existingSet->set_num = '75192-1';
            $existingSet->shouldReceive('setParts')->once()->andReturn($setPartsRelation);
            $existingSet->shouldReceive('load')->with(['setParts.part', 'setParts.color'])->once()->andReturnSelf();

            $queryBuilder = Mockery::mock(Builder::class);
            $queryBuilder->shouldReceive('where')->with('set_num', '75192-1')->once()->andReturnSelf();
            $queryBuilder->shouldReceive('first')->once()->andReturn($existingSet);

            $set = Mockery::mock(Set::class);
            $set->shouldReceive('newQuery')->once()->andReturn($queryBuilder);

            $part = Mockery::mock(Part::class);
            $color = Mockery::mock(Color::class);
            $setPart = Mockery::mock(SetPart::class);

            $service = new RebrickableService($set, $part, $color, $setPart);

            // act
            $result = $service->getSetParts('75192-1');

            // assert
            expect($result)->toBe($existingSet);
            Http::assertNothingSent();
        });

        it('should fetch from API when set does not exist in database', function (): void {
            // arrange
            Http::fake([
                'https://rebrickable.com/api/v3/lego/sets/75192-1/' => Http::response([
                    'set_num' => '75192-1',
                    'name' => 'Millennium Falcon',
                    'year' => 2017,
                    'theme_id' => 158,
                    'num_parts' => 7541,
                    'set_img_url' => 'https://example.com/75192.jpg',
                ]),
                'https://rebrickable.com/api/v3/lego/sets/75192-1/parts/' => Http::response([
                    'results' => [
                        [
                            'part' => [
                                'part_num' => '3001',
                                'name' => 'Brick 2 x 4',
                                'part_cat_id' => 11,
                                'part_img_url' => 'https://example.com/3001.jpg',
                            ],
                            'color' => [
                                'id' => 1,
                                'name' => 'White',
                                'rgb' => 'FFFFFF',
                                'is_trans' => false,
                            ],
                            'quantity' => 10,
                            'is_spare' => false,
                            'element_id' => '300101',
                        ],
                    ],
                    'next' => null,
                ]),
            ]);

            // Set query - returns null (set doesn't exist)
            $setQueryBuilder = Mockery::mock(Builder::class);
            $setQueryBuilder->shouldReceive('where')->andReturnSelf();
            $setQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdSet = Mockery::mock(Set::class)->makePartial();
            $createdSet->id = 1;
            $createdSet->shouldReceive('save')->once();
            $createdSet->shouldReceive('load')->with(['setParts.part', 'setParts.color'])->once()->andReturnSelf();

            $set = Mockery::mock(Set::class);
            $set->shouldReceive('newQuery')->andReturn($setQueryBuilder);
            $set->shouldReceive('newInstance')->once()->andReturn($createdSet);

            // Color query - returns null (color doesn't exist)
            $colorQueryBuilder = Mockery::mock(Builder::class);
            $colorQueryBuilder->shouldReceive('where')->andReturnSelf();
            $colorQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdColor = Mockery::mock(Color::class)->makePartial();
            $createdColor->id = 1;
            $createdColor->shouldReceive('save')->once();

            $color = Mockery::mock(Color::class);
            $color->shouldReceive('newQuery')->andReturn($colorQueryBuilder);
            $color->shouldReceive('newInstance')->once()->andReturn($createdColor);

            // Part query - returns null (part doesn't exist)
            $partQueryBuilder = Mockery::mock(Builder::class);
            $partQueryBuilder->shouldReceive('where')->andReturnSelf();
            $partQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdPart = Mockery::mock(Part::class)->makePartial();
            $createdPart->id = 1;
            $createdPart->shouldReceive('save')->once();

            $part = Mockery::mock(Part::class);
            $part->shouldReceive('newQuery')->andReturn($partQueryBuilder);
            $part->shouldReceive('newInstance')->once()->andReturn($createdPart);

            // SetPart query - returns null (set part doesn't exist)
            $setPartQueryBuilder = Mockery::mock(Builder::class);
            $setPartQueryBuilder->shouldReceive('where')->andReturnSelf();
            $setPartQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdSetPart = Mockery::mock(SetPart::class)->makePartial();
            $createdSetPart->shouldReceive('save')->once();

            $setPart = Mockery::mock(SetPart::class);
            $setPart->shouldReceive('newQuery')->andReturn($setPartQueryBuilder);
            $setPart->shouldReceive('newInstance')->once()->andReturn($createdSetPart);

            $service = new RebrickableService($set, $part, $color, $setPart);

            // act
            $result = $service->getSetParts('75192-1');

            // assert
            expect($result)->toBe($createdSet);
            expect($result->set_num)->toBe('75192-1');
            expect($result->name)->toBe('Millennium Falcon');
        });

        it('should fetch from API when set exists but has no parts', function (): void {
            // arrange
            Http::fake([
                'https://rebrickable.com/api/v3/lego/sets/75192-1/' => Http::response([
                    'set_num' => '75192-1',
                    'name' => 'Millennium Falcon',
                    'year' => 2017,
                    'theme_id' => 158,
                    'num_parts' => 7541,
                    'set_img_url' => 'https://example.com/75192.jpg',
                ]),
                'https://rebrickable.com/api/v3/lego/sets/75192-1/parts/' => Http::response([
                    'results' => [],
                    'next' => null,
                ]),
            ]);

            $setPartsRelation = Mockery::mock(HasMany::class);
            $setPartsRelation->shouldReceive('exists')->once()->andReturn(false);

            $existingSet = Mockery::mock(Set::class)->makePartial();
            $existingSet->id = 1;
            $existingSet->set_num = '75192-1';
            $existingSet->shouldReceive('setParts')->once()->andReturn($setPartsRelation);
            $existingSet->shouldReceive('save')->once();
            $existingSet->shouldReceive('load')->with(['setParts.part', 'setParts.color'])->once()->andReturnSelf();

            $setQueryBuilder = Mockery::mock(Builder::class);
            $setQueryBuilder->shouldReceive('where')->andReturnSelf();
            $setQueryBuilder->shouldReceive('first')->andReturn($existingSet);

            $set = Mockery::mock(Set::class);
            $set->shouldReceive('newQuery')->andReturn($setQueryBuilder);

            $part = Mockery::mock(Part::class);
            $color = Mockery::mock(Color::class);
            $setPart = Mockery::mock(SetPart::class);

            $service = new RebrickableService($set, $part, $color, $setPart);

            // act
            $result = $service->getSetParts('75192-1');

            // assert
            expect($result)->toBe($existingSet);
            expect($result->name)->toBe('Millennium Falcon');
        });

        it('should handle API pagination when fetching parts', function (): void {
            // arrange
            Http::fake([
                'https://rebrickable.com/api/v3/lego/sets/75192-1/' => Http::response([
                    'set_num' => '75192-1',
                    'name' => 'Millennium Falcon',
                    'year' => 2017,
                    'theme_id' => 158,
                    'num_parts' => 2,
                    'set_img_url' => null,
                ]),
                'https://rebrickable.com/api/v3/lego/sets/75192-1/parts/' => Http::response([
                    'results' => [
                        [
                            'part' => ['part_num' => '3001', 'name' => 'Brick 2 x 4', 'part_cat_id' => 11, 'part_img_url' => null],
                            'color' => ['id' => 1, 'name' => 'White', 'rgb' => 'FFFFFF', 'is_trans' => false],
                            'quantity' => 5,
                            'is_spare' => false,
                            'element_id' => '300101',
                        ],
                    ],
                    'next' => 'https://rebrickable.com/api/v3/lego/sets/75192-1/parts/?page=2',
                ]),
                'https://rebrickable.com/api/v3/lego/sets/75192-1/parts/?page=2' => Http::response([
                    'results' => [
                        [
                            'part' => ['part_num' => '3002', 'name' => 'Brick 2 x 3', 'part_cat_id' => null, 'part_img_url' => null],
                            'color' => ['id' => 2, 'name' => 'Black', 'rgb' => '000000', 'is_trans' => false],
                            'quantity' => 3,
                            'is_spare' => true,
                            'element_id' => null,
                        ],
                    ],
                    'next' => null,
                ]),
            ]);

            $setQueryBuilder = Mockery::mock(Builder::class);
            $setQueryBuilder->shouldReceive('where')->andReturnSelf();
            $setQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdSet = Mockery::mock(Set::class)->makePartial();
            $createdSet->id = 1;
            $createdSet->shouldReceive('save')->once();
            $createdSet->shouldReceive('load')->with(['setParts.part', 'setParts.color'])->once()->andReturnSelf();

            $set = Mockery::mock(Set::class);
            $set->shouldReceive('newQuery')->andReturn($setQueryBuilder);
            $set->shouldReceive('newInstance')->once()->andReturn($createdSet);

            // Two colors (one per part)
            $colorQueryBuilder = Mockery::mock(Builder::class);
            $colorQueryBuilder->shouldReceive('where')->andReturnSelf();
            $colorQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdColor1 = Mockery::mock(Color::class)->makePartial();
            $createdColor1->id = 1;
            $createdColor1->shouldReceive('save')->once();

            $createdColor2 = Mockery::mock(Color::class)->makePartial();
            $createdColor2->id = 2;
            $createdColor2->shouldReceive('save')->once();

            $color = Mockery::mock(Color::class);
            $color->shouldReceive('newQuery')->andReturn($colorQueryBuilder);
            $color->shouldReceive('newInstance')->twice()->andReturn($createdColor1, $createdColor2);

            // Two parts
            $partQueryBuilder = Mockery::mock(Builder::class);
            $partQueryBuilder->shouldReceive('where')->andReturnSelf();
            $partQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdPart1 = Mockery::mock(Part::class)->makePartial();
            $createdPart1->id = 1;
            $createdPart1->shouldReceive('save')->once();

            $createdPart2 = Mockery::mock(Part::class)->makePartial();
            $createdPart2->id = 2;
            $createdPart2->shouldReceive('save')->once();

            $part = Mockery::mock(Part::class);
            $part->shouldReceive('newQuery')->andReturn($partQueryBuilder);
            $part->shouldReceive('newInstance')->twice()->andReturn($createdPart1, $createdPart2);

            // Two set parts
            $setPartQueryBuilder = Mockery::mock(Builder::class);
            $setPartQueryBuilder->shouldReceive('where')->andReturnSelf();
            $setPartQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdSetPart1 = Mockery::mock(SetPart::class)->makePartial();
            $createdSetPart1->shouldReceive('save')->once();

            $createdSetPart2 = Mockery::mock(SetPart::class)->makePartial();
            $createdSetPart2->shouldReceive('save')->once();

            $setPart = Mockery::mock(SetPart::class);
            $setPart->shouldReceive('newQuery')->andReturn($setPartQueryBuilder);
            $setPart->shouldReceive('newInstance')->twice()->andReturn($createdSetPart1, $createdSetPart2);

            $service = new RebrickableService($set, $part, $color, $setPart);

            // act
            $result = $service->getSetParts('75192-1');

            // assert
            expect($result)->toBe($createdSet);
            Http::assertSentCount(3); // set, parts page 1, parts page 2
        });

        it('should update existing color when it already exists', function (): void {
            // arrange
            Http::fake([
                'https://rebrickable.com/api/v3/lego/sets/75192-1/' => Http::response([
                    'set_num' => '75192-1',
                    'name' => 'Millennium Falcon',
                    'year' => 2017,
                    'theme_id' => 158,
                    'num_parts' => 1,
                    'set_img_url' => null,
                ]),
                'https://rebrickable.com/api/v3/lego/sets/75192-1/parts/' => Http::response([
                    'results' => [
                        [
                            'part' => ['part_num' => '3001', 'name' => 'Brick 2 x 4', 'part_cat_id' => 11, 'part_img_url' => null],
                            'color' => ['id' => 1, 'name' => 'Updated White', 'rgb' => 'FFFFF0', 'is_trans' => false],
                            'quantity' => 5,
                            'is_spare' => false,
                            'element_id' => '300101',
                        ],
                    ],
                    'next' => null,
                ]),
            ]);

            $setQueryBuilder = Mockery::mock(Builder::class);
            $setQueryBuilder->shouldReceive('where')->andReturnSelf();
            $setQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdSet = Mockery::mock(Set::class)->makePartial();
            $createdSet->id = 1;
            $createdSet->shouldReceive('save')->once();
            $createdSet->shouldReceive('load')->with(['setParts.part', 'setParts.color'])->once()->andReturnSelf();

            $set = Mockery::mock(Set::class);
            $set->shouldReceive('newQuery')->andReturn($setQueryBuilder);
            $set->shouldReceive('newInstance')->once()->andReturn($createdSet);

            // Existing color
            $existingColor = Mockery::mock(Color::class)->makePartial();
            $existingColor->id = 1;
            $existingColor->rebrickable_id = 1;
            $existingColor->shouldReceive('save')->once();

            $colorQueryBuilder = Mockery::mock(Builder::class);
            $colorQueryBuilder->shouldReceive('where')->with('rebrickable_id', 1)->andReturnSelf();
            $colorQueryBuilder->shouldReceive('first')->andReturn($existingColor);

            $color = Mockery::mock(Color::class);
            $color->shouldReceive('newQuery')->andReturn($colorQueryBuilder);
            // No newInstance call because color exists

            // Part query
            $partQueryBuilder = Mockery::mock(Builder::class);
            $partQueryBuilder->shouldReceive('where')->andReturnSelf();
            $partQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdPart = Mockery::mock(Part::class)->makePartial();
            $createdPart->id = 1;
            $createdPart->shouldReceive('save')->once();

            $part = Mockery::mock(Part::class);
            $part->shouldReceive('newQuery')->andReturn($partQueryBuilder);
            $part->shouldReceive('newInstance')->once()->andReturn($createdPart);

            // SetPart query
            $setPartQueryBuilder = Mockery::mock(Builder::class);
            $setPartQueryBuilder->shouldReceive('where')->andReturnSelf();
            $setPartQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdSetPart = Mockery::mock(SetPart::class)->makePartial();
            $createdSetPart->shouldReceive('save')->once();

            $setPart = Mockery::mock(SetPart::class);
            $setPart->shouldReceive('newQuery')->andReturn($setPartQueryBuilder);
            $setPart->shouldReceive('newInstance')->once()->andReturn($createdSetPart);

            $service = new RebrickableService($set, $part, $color, $setPart);

            // act
            $result = $service->getSetParts('75192-1');

            // assert
            expect($existingColor->name)->toBe('Updated White');
            expect($existingColor->rgb)->toBe('FFFFF0');
        });

        it('should update existing part when it already exists', function (): void {
            // arrange
            Http::fake([
                'https://rebrickable.com/api/v3/lego/sets/75192-1/' => Http::response([
                    'set_num' => '75192-1',
                    'name' => 'Millennium Falcon',
                    'year' => 2017,
                    'theme_id' => null,
                    'num_parts' => 1,
                    'set_img_url' => null,
                ]),
                'https://rebrickable.com/api/v3/lego/sets/75192-1/parts/' => Http::response([
                    'results' => [
                        [
                            'part' => ['part_num' => '3001', 'name' => 'Updated Brick', 'part_cat_id' => null, 'part_img_url' => 'https://new.img'],
                            'color' => ['id' => 1, 'name' => 'White', 'rgb' => 'FFFFFF', 'is_trans' => false],
                            'quantity' => 5,
                            'is_spare' => false,
                            'element_id' => null,
                        ],
                    ],
                    'next' => null,
                ]),
            ]);

            $setQueryBuilder = Mockery::mock(Builder::class);
            $setQueryBuilder->shouldReceive('where')->andReturnSelf();
            $setQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdSet = Mockery::mock(Set::class)->makePartial();
            $createdSet->id = 1;
            $createdSet->shouldReceive('save')->once();
            $createdSet->shouldReceive('load')->with(['setParts.part', 'setParts.color'])->once()->andReturnSelf();

            $set = Mockery::mock(Set::class);
            $set->shouldReceive('newQuery')->andReturn($setQueryBuilder);
            $set->shouldReceive('newInstance')->once()->andReturn($createdSet);

            // Color query
            $colorQueryBuilder = Mockery::mock(Builder::class);
            $colorQueryBuilder->shouldReceive('where')->andReturnSelf();
            $colorQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdColor = Mockery::mock(Color::class)->makePartial();
            $createdColor->id = 1;
            $createdColor->shouldReceive('save')->once();

            $color = Mockery::mock(Color::class);
            $color->shouldReceive('newQuery')->andReturn($colorQueryBuilder);
            $color->shouldReceive('newInstance')->once()->andReturn($createdColor);

            // Existing part
            $existingPart = Mockery::mock(Part::class)->makePartial();
            $existingPart->id = 1;
            $existingPart->part_num = '3001';
            $existingPart->shouldReceive('save')->once();

            $partQueryBuilder = Mockery::mock(Builder::class);
            $partQueryBuilder->shouldReceive('where')->with('part_num', '3001')->andReturnSelf();
            $partQueryBuilder->shouldReceive('first')->andReturn($existingPart);

            $part = Mockery::mock(Part::class);
            $part->shouldReceive('newQuery')->andReturn($partQueryBuilder);

            // SetPart query
            $setPartQueryBuilder = Mockery::mock(Builder::class);
            $setPartQueryBuilder->shouldReceive('where')->andReturnSelf();
            $setPartQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdSetPart = Mockery::mock(SetPart::class)->makePartial();
            $createdSetPart->shouldReceive('save')->once();

            $setPart = Mockery::mock(SetPart::class);
            $setPart->shouldReceive('newQuery')->andReturn($setPartQueryBuilder);
            $setPart->shouldReceive('newInstance')->once()->andReturn($createdSetPart);

            $service = new RebrickableService($set, $part, $color, $setPart);

            // act
            $result = $service->getSetParts('75192-1');

            // assert
            expect($existingPart->name)->toBe('Updated Brick');
            expect($existingPart->category)->toBeNull();
            expect($existingPart->image_url)->toBe('https://new.img');
        });

        it('should update existing set part when it already exists', function (): void {
            // arrange
            Http::fake([
                'https://rebrickable.com/api/v3/lego/sets/75192-1/' => Http::response([
                    'set_num' => '75192-1',
                    'name' => 'Millennium Falcon',
                    'year' => 2017,
                    'theme_id' => 158,
                    'num_parts' => 1,
                    'set_img_url' => null,
                ]),
                'https://rebrickable.com/api/v3/lego/sets/75192-1/parts/' => Http::response([
                    'results' => [
                        [
                            'part' => ['part_num' => '3001', 'name' => 'Brick 2 x 4', 'part_cat_id' => 11, 'part_img_url' => null],
                            'color' => ['id' => 1, 'name' => 'White', 'rgb' => 'FFFFFF', 'is_trans' => false],
                            'quantity' => 15,
                            'is_spare' => false,
                            'element_id' => 'NEW123',
                        ],
                    ],
                    'next' => null,
                ]),
            ]);

            $setQueryBuilder = Mockery::mock(Builder::class);
            $setQueryBuilder->shouldReceive('where')->andReturnSelf();
            $setQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdSet = Mockery::mock(Set::class)->makePartial();
            $createdSet->id = 1;
            $createdSet->shouldReceive('save')->once();
            $createdSet->shouldReceive('load')->with(['setParts.part', 'setParts.color'])->once()->andReturnSelf();

            $set = Mockery::mock(Set::class);
            $set->shouldReceive('newQuery')->andReturn($setQueryBuilder);
            $set->shouldReceive('newInstance')->once()->andReturn($createdSet);

            // Color
            $colorQueryBuilder = Mockery::mock(Builder::class);
            $colorQueryBuilder->shouldReceive('where')->andReturnSelf();
            $colorQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdColor = Mockery::mock(Color::class)->makePartial();
            $createdColor->id = 1;
            $createdColor->shouldReceive('save')->once();

            $color = Mockery::mock(Color::class);
            $color->shouldReceive('newQuery')->andReturn($colorQueryBuilder);
            $color->shouldReceive('newInstance')->once()->andReturn($createdColor);

            // Part
            $partQueryBuilder = Mockery::mock(Builder::class);
            $partQueryBuilder->shouldReceive('where')->andReturnSelf();
            $partQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdPart = Mockery::mock(Part::class)->makePartial();
            $createdPart->id = 1;
            $createdPart->shouldReceive('save')->once();

            $part = Mockery::mock(Part::class);
            $part->shouldReceive('newQuery')->andReturn($partQueryBuilder);
            $part->shouldReceive('newInstance')->once()->andReturn($createdPart);

            // Existing set part
            $existingSetPart = Mockery::mock(SetPart::class)->makePartial();
            $existingSetPart->set_id = 1;
            $existingSetPart->part_id = 1;
            $existingSetPart->color_id = 1;
            $existingSetPart->is_spare = false;
            $existingSetPart->quantity = 10;
            $existingSetPart->shouldReceive('save')->once();

            $setPartQueryBuilder = Mockery::mock(Builder::class);
            $setPartQueryBuilder->shouldReceive('where')->andReturnSelf();
            $setPartQueryBuilder->shouldReceive('first')->andReturn($existingSetPart);

            $setPart = Mockery::mock(SetPart::class);
            $setPart->shouldReceive('newQuery')->andReturn($setPartQueryBuilder);

            $service = new RebrickableService($set, $part, $color, $setPart);

            // act
            $result = $service->getSetParts('75192-1');

            // assert
            expect($existingSetPart->quantity)->toBe(15);
            expect($existingSetPart->element_id)->toBe('NEW123');
        });

        it('should handle transparent colors', function (): void {
            // arrange
            Http::fake([
                'https://rebrickable.com/api/v3/lego/sets/75192-1/' => Http::response([
                    'set_num' => '75192-1',
                    'name' => 'Millennium Falcon',
                    'year' => 2017,
                    'theme_id' => 158,
                    'num_parts' => 1,
                    'set_img_url' => null,
                ]),
                'https://rebrickable.com/api/v3/lego/sets/75192-1/parts/' => Http::response([
                    'results' => [
                        [
                            'part' => ['part_num' => '3001', 'name' => 'Brick 2 x 4', 'part_cat_id' => 11, 'part_img_url' => null],
                            'color' => ['id' => 41, 'name' => 'Trans-Red', 'rgb' => 'FF0000', 'is_trans' => true],
                            'quantity' => 5,
                            'is_spare' => false,
                            'element_id' => null,
                        ],
                    ],
                    'next' => null,
                ]),
            ]);

            $setQueryBuilder = Mockery::mock(Builder::class);
            $setQueryBuilder->shouldReceive('where')->andReturnSelf();
            $setQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdSet = Mockery::mock(Set::class)->makePartial();
            $createdSet->id = 1;
            $createdSet->shouldReceive('save')->once();
            $createdSet->shouldReceive('load')->with(['setParts.part', 'setParts.color'])->once()->andReturnSelf();

            $set = Mockery::mock(Set::class);
            $set->shouldReceive('newQuery')->andReturn($setQueryBuilder);
            $set->shouldReceive('newInstance')->once()->andReturn($createdSet);

            // Color
            $colorQueryBuilder = Mockery::mock(Builder::class);
            $colorQueryBuilder->shouldReceive('where')->andReturnSelf();
            $colorQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdColor = Mockery::mock(Color::class)->makePartial();
            $createdColor->id = 1;
            $createdColor->shouldReceive('save')->once();

            $color = Mockery::mock(Color::class);
            $color->shouldReceive('newQuery')->andReturn($colorQueryBuilder);
            $color->shouldReceive('newInstance')->once()->andReturn($createdColor);

            // Part
            $partQueryBuilder = Mockery::mock(Builder::class);
            $partQueryBuilder->shouldReceive('where')->andReturnSelf();
            $partQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdPart = Mockery::mock(Part::class)->makePartial();
            $createdPart->id = 1;
            $createdPart->shouldReceive('save')->once();

            $part = Mockery::mock(Part::class);
            $part->shouldReceive('newQuery')->andReturn($partQueryBuilder);
            $part->shouldReceive('newInstance')->once()->andReturn($createdPart);

            // SetPart
            $setPartQueryBuilder = Mockery::mock(Builder::class);
            $setPartQueryBuilder->shouldReceive('where')->andReturnSelf();
            $setPartQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdSetPart = Mockery::mock(SetPart::class)->makePartial();
            $createdSetPart->shouldReceive('save')->once();

            $setPart = Mockery::mock(SetPart::class);
            $setPart->shouldReceive('newQuery')->andReturn($setPartQueryBuilder);
            $setPart->shouldReceive('newInstance')->once()->andReturn($createdSetPart);

            $service = new RebrickableService($set, $part, $color, $setPart);

            // act
            $result = $service->getSetParts('75192-1');

            // assert
            expect($createdColor->name)->toBe('Trans-Red');
            expect($createdColor->is_transparent)->toBeTrue();
        });

        it('should throw RequestException when parts API fails', function (): void {
            // arrange
            Http::fake([
                'https://rebrickable.com/api/v3/lego/sets/75192-1/' => Http::response([
                    'set_num' => '75192-1',
                    'name' => 'Millennium Falcon',
                    'year' => 2017,
                    'theme_id' => 158,
                    'num_parts' => 7541,
                    'set_img_url' => null,
                ]),
                'https://rebrickable.com/api/v3/lego/sets/75192-1/parts/' => Http::response([], 500),
            ]);

            $setQueryBuilder = Mockery::mock(Builder::class);
            $setQueryBuilder->shouldReceive('where')->andReturnSelf();
            $setQueryBuilder->shouldReceive('first')->andReturn(null);

            $createdSet = Mockery::mock(Set::class)->makePartial();
            $createdSet->id = 1;
            $createdSet->shouldReceive('save')->once();

            $set = Mockery::mock(Set::class);
            $set->shouldReceive('newQuery')->andReturn($setQueryBuilder);
            $set->shouldReceive('newInstance')->once()->andReturn($createdSet);

            $part = Mockery::mock(Part::class);
            $color = Mockery::mock(Color::class);
            $setPart = Mockery::mock(SetPart::class);

            $service = new RebrickableService($set, $part, $color, $setPart);

            // act & assert
            expect(fn (): Set => $service->getSetParts('75192-1'))->toThrow(RequestException::class);
        });
    });
});

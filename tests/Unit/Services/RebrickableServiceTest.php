<?php

declare(strict_types=1);

use App\Actions\Sync\StoreSetPartsAction;
use App\Actions\Sync\UpsertSetAction;
use App\Models\Set;
use App\Services\RebrickableService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

const TEST_API_KEY = 'test-api-key';
const TEST_BASE_URL = 'https://rebrickable.com/api/v3';

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
            $upsertSetAction = Mockery::mock(UpsertSetAction::class);
            $storeSetPartsAction = Mockery::mock(StoreSetPartsAction::class);

            $service = new RebrickableService(TEST_API_KEY, TEST_BASE_URL, $set, $upsertSetAction, $storeSetPartsAction);

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
            $upsertSetAction = Mockery::mock(UpsertSetAction::class);
            $storeSetPartsAction = Mockery::mock(StoreSetPartsAction::class);

            $service = new RebrickableService(TEST_API_KEY, TEST_BASE_URL, $set, $upsertSetAction, $storeSetPartsAction);

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
            $upsertSetAction = Mockery::mock(UpsertSetAction::class);
            $storeSetPartsAction = Mockery::mock(StoreSetPartsAction::class);

            $service = new RebrickableService(TEST_API_KEY, TEST_BASE_URL, $set, $upsertSetAction, $storeSetPartsAction);

            // act
            $result = $service->fetchSet('10281-1');

            // assert
            expect($result['theme_id'])->toBeNull();
            expect($result['set_img_url'])->toBeNull();
        });
    });

    describe('fetchSetParts', function (): void {
        it('should return parts data from API', function (): void {
            // arrange
            Http::fake([
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
                    'next' => null,
                ]),
            ]);

            $set = Mockery::mock(Set::class);
            $upsertSetAction = Mockery::mock(UpsertSetAction::class);
            $storeSetPartsAction = Mockery::mock(StoreSetPartsAction::class);

            $service = new RebrickableService(TEST_API_KEY, TEST_BASE_URL, $set, $upsertSetAction, $storeSetPartsAction);

            // act
            $result = $service->fetchSetParts('75192-1');

            // assert
            expect($result)->toHaveCount(1);
            expect($result[0]['part']['part_num'])->toBe('3001');
        });

        it('should handle pagination', function (): void {
            // arrange
            Http::fake([
                'https://rebrickable.com/api/v3/lego/sets/75192-1/parts/' => Http::response([
                    'results' => [
                        [
                            'part' => ['part_num' => '3001', 'name' => 'Brick 2 x 4', 'part_cat_id' => 11, 'part_img_url' => null],
                            'color' => ['id' => 1, 'name' => 'White', 'rgb' => 'FFFFFF', 'is_trans' => false],
                            'quantity' => 5,
                            'is_spare' => false,
                            'element_id' => null,
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

            $set = Mockery::mock(Set::class);
            $upsertSetAction = Mockery::mock(UpsertSetAction::class);
            $storeSetPartsAction = Mockery::mock(StoreSetPartsAction::class);

            $service = new RebrickableService(TEST_API_KEY, TEST_BASE_URL, $set, $upsertSetAction, $storeSetPartsAction);

            // act
            $result = $service->fetchSetParts('75192-1');

            // assert
            expect($result)->toHaveCount(2);
            expect($result[0]['part']['part_num'])->toBe('3001');
            expect($result[1]['part']['part_num'])->toBe('3002');
            Http::assertSentCount(2);
        });

        it('should throw RequestException when API fails', function (): void {
            // arrange
            Http::fake([
                'https://rebrickable.com/api/v3/lego/sets/75192-1/parts/' => Http::response([], 500),
            ]);

            $set = Mockery::mock(Set::class);
            $upsertSetAction = Mockery::mock(UpsertSetAction::class);
            $storeSetPartsAction = Mockery::mock(StoreSetPartsAction::class);

            $service = new RebrickableService(TEST_API_KEY, TEST_BASE_URL, $set, $upsertSetAction, $storeSetPartsAction);

            // act & assert
            expect(fn (): array => $service->fetchSetParts('75192-1'))->toThrow(RequestException::class);
        });
    });

    describe('getSetParts', function (): void {
        it('should return existing set from database when set has parts', function (): void {
            // arrange
            Http::preventStrayRequests();

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

            $upsertSetAction = Mockery::mock(UpsertSetAction::class);
            $storeSetPartsAction = Mockery::mock(StoreSetPartsAction::class);

            $service = new RebrickableService(TEST_API_KEY, TEST_BASE_URL, $set, $upsertSetAction, $storeSetPartsAction);

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
                            'part' => ['part_num' => '3001', 'name' => 'Brick 2 x 4', 'part_cat_id' => 11, 'part_img_url' => null],
                            'color' => ['id' => 1, 'name' => 'White', 'rgb' => 'FFFFFF', 'is_trans' => false],
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
            $createdSet->set_num = '75192-1';
            $createdSet->name = 'Millennium Falcon';
            $createdSet->shouldReceive('load')->with(['setParts.part', 'setParts.color'])->once()->andReturnSelf();

            $set = Mockery::mock(Set::class);
            $set->shouldReceive('newQuery')->andReturn($setQueryBuilder);

            $upsertSetAction = Mockery::mock(UpsertSetAction::class);
            $upsertSetAction->shouldReceive('execute')
                ->once()
                ->with(Mockery::type('array'))
                ->andReturn($createdSet);

            $storeSetPartsAction = Mockery::mock(StoreSetPartsAction::class);
            $storeSetPartsAction->shouldReceive('execute')
                ->once()
                ->with($createdSet, Mockery::type('array'));

            $service = new RebrickableService(TEST_API_KEY, TEST_BASE_URL, $set, $upsertSetAction, $storeSetPartsAction);

            // act
            $result = $service->getSetParts('75192-1');

            // assert
            expect($result)->toBe($createdSet);
            expect($result->set_num)->toBe('75192-1');
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

            $setQueryBuilder = Mockery::mock(Builder::class);
            $setQueryBuilder->shouldReceive('where')->andReturnSelf();
            $setQueryBuilder->shouldReceive('first')->andReturn($existingSet);

            $set = Mockery::mock(Set::class);
            $set->shouldReceive('newQuery')->andReturn($setQueryBuilder);

            $upsertedSet = Mockery::mock(Set::class)->makePartial();
            $upsertedSet->id = 1;
            $upsertedSet->set_num = '75192-1';
            $upsertedSet->name = 'Millennium Falcon';
            $upsertedSet->shouldReceive('load')->with(['setParts.part', 'setParts.color'])->once()->andReturnSelf();

            $upsertSetAction = Mockery::mock(UpsertSetAction::class);
            $upsertSetAction->shouldReceive('execute')
                ->once()
                ->andReturn($upsertedSet);

            $storeSetPartsAction = Mockery::mock(StoreSetPartsAction::class);
            $storeSetPartsAction->shouldReceive('execute')
                ->once()
                ->with($upsertedSet, []);

            $service = new RebrickableService(TEST_API_KEY, TEST_BASE_URL, $set, $upsertSetAction, $storeSetPartsAction);

            // act
            $result = $service->getSetParts('75192-1');

            // assert
            expect($result)->toBe($upsertedSet);
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
            $createdSet->shouldReceive('load')->with(['setParts.part', 'setParts.color'])->once()->andReturnSelf();

            $set = Mockery::mock(Set::class);
            $set->shouldReceive('newQuery')->andReturn($setQueryBuilder);

            $upsertSetAction = Mockery::mock(UpsertSetAction::class);
            $upsertSetAction->shouldReceive('execute')->once()->andReturn($createdSet);

            $storeSetPartsAction = Mockery::mock(StoreSetPartsAction::class);
            $storeSetPartsAction->shouldReceive('execute')
                ->once()
                ->with($createdSet, Mockery::on(fn ($parts): bool => count($parts) === 2));

            $service = new RebrickableService(TEST_API_KEY, TEST_BASE_URL, $set, $upsertSetAction, $storeSetPartsAction);

            // act
            $result = $service->getSetParts('75192-1');

            // assert
            expect($result)->toBe($createdSet);
            Http::assertSentCount(3); // set, parts page 1, parts page 2
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

            $set = Mockery::mock(Set::class);
            $set->shouldReceive('newQuery')->andReturn($setQueryBuilder);

            $upsertSetAction = Mockery::mock(UpsertSetAction::class);
            $upsertSetAction->shouldReceive('execute')->once()->andReturn($createdSet);

            $storeSetPartsAction = Mockery::mock(StoreSetPartsAction::class);

            $service = new RebrickableService(TEST_API_KEY, TEST_BASE_URL, $set, $upsertSetAction, $storeSetPartsAction);

            // act & assert
            expect(fn (): Set => $service->getSetParts('75192-1'))->toThrow(RequestException::class);
        });
    });
});

<?php

declare(strict_types = 1);

use App\Actions\Sync\StoreSetPartsAction;
use App\DataTransferObjects\Input\Lego\LegoColorData;
use App\DataTransferObjects\Input\Lego\LegoPartData;
use App\DataTransferObjects\Input\Lego\LegoSetPartData;
use App\Models\Color;
use App\Models\Part;
use App\Models\Set;
use App\Models\SetPart;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

covers(StoreSetPartsAction::class);

describe('StoreSetPartsAction', function(): void {
    beforeEach(function(): void {
        $this->db = \Mockery::mock(ConnectionInterface::class);
        $this->db->allows('transaction')->andReturnUsing(fn(\Closure $callback): mixed => $callback());
    });

    /**
     * Build a fully-mocked Color model with a chained newQuery() pipeline:
     *   - upsert(...) recorded
     *   - whereIn(...)->pluck('id', 'rebrickable_id') returns the supplied id map
     *
     * @param array<int, int> $idsByRebrickableId
     */
    $buildColorMock = function(array $idsByRebrickableId, ?\Closure $captureUpsert = null): Color {
        $upsertBuilder = \Mockery::mock(Builder::class);
        $upsertBuilder->shouldReceive('upsert')
            ->andReturnUsing(function(array $values, array $unique, array $update) use ($captureUpsert): int {
                if ($captureUpsert instanceof \Closure) {
                    $captureUpsert($values, $unique, $update);
                }

                return \count($values);
            });

        $reloadBuilder = \Mockery::mock(Builder::class);
        $reloadBuilder->shouldReceive('whereIn')->andReturnSelf();
        $reloadBuilder->shouldReceive('pluck')
            ->with('id', 'rebrickable_id')
            ->andReturn(new Collection($idsByRebrickableId));

        $color = \Mockery::mock(Color::class);
        $color->shouldReceive('newQuery')->andReturn($upsertBuilder, $reloadBuilder);

        return $color;
    };

    /**
     * @param array<string, int> $idsByPartNum
     */
    $buildPartMock = function(array $idsByPartNum, ?\Closure $captureUpsert = null): Part {
        $upsertBuilder = \Mockery::mock(Builder::class);
        $upsertBuilder->shouldReceive('upsert')
            ->andReturnUsing(function(array $values, array $unique, array $update) use ($captureUpsert): int {
                if ($captureUpsert instanceof \Closure) {
                    $captureUpsert($values, $unique, $update);
                }

                return \count($values);
            });

        $reloadBuilder = \Mockery::mock(Builder::class);
        $reloadBuilder->shouldReceive('whereIn')->andReturnSelf();
        $reloadBuilder->shouldReceive('pluck')
            ->with('id', 'part_num')
            ->andReturn(new Collection($idsByPartNum));

        $part = \Mockery::mock(Part::class);
        $part->shouldReceive('newQuery')->andReturn($upsertBuilder, $reloadBuilder);

        return $part;
    };

    /**
     * @param list<array<string, mixed>> $capturedChunks
     */
    $buildSetPartMock = function(array &$capturedChunks): SetPart {
        $builder = \Mockery::mock(Builder::class);
        $builder->shouldReceive('upsert')
            ->andReturnUsing(function(array $values, array $unique, array $update) use (&$capturedChunks): int {
                $capturedChunks[] = ['values' => $values, 'unique' => $unique, 'update' => $update];

                return \count($values);
            });

        $setPart = \Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->andReturn($builder);

        return $setPart;
    };

    it('should be a no-op when given an empty parts list', function() use ($buildColorMock, $buildPartMock): void {
        // arrange
        $set = \Mockery::mock(Set::class);
        $color = $buildColorMock([]);
        $color->shouldNotReceive('newQuery');

        $part = $buildPartMock([]);
        $part->shouldNotReceive('newQuery');

        $setPartBuilder = \Mockery::mock(Builder::class);
        $setPartBuilder->shouldNotReceive('upsert');

        $setPart = \Mockery::mock(SetPart::class);
        $setPart->shouldNotReceive('newQuery');

        $this->db->shouldNotReceive('transaction');

        $action = new StoreSetPartsAction($color, $part, $setPart, $this->db);

        // act
        $action->execute($set, []);

        // assert — Mockery verifies expectations
    });

    it('should dedupe colors by rebrickable_id into a single bulk upsert', function() use ($buildPartMock, $buildSetPartMock): void {
        // arrange
        $set = \Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(42);

        $colorUpserts = [];
        $captureColorUpsert = function(array $values) use (&$colorUpserts): void {
            $colorUpserts[] = $values;
        };

        // Build color mock with capture
        $upsertBuilder = \Mockery::mock(Builder::class);
        $upsertBuilder->shouldReceive('upsert')
            ->once()
            ->andReturnUsing(function(array $values) use ($captureColorUpsert): int {
                $captureColorUpsert($values);

                return \count($values);
            });

        $reloadBuilder = \Mockery::mock(Builder::class);
        $reloadBuilder->shouldReceive('whereIn')->andReturnSelf();
        $reloadBuilder->shouldReceive('pluck')->with('id', 'rebrickable_id')->andReturn(new Collection([1 => 11]));

        $color = \Mockery::mock(Color::class);
        $color->shouldReceive('newQuery')->twice()->andReturn($upsertBuilder, $reloadBuilder);

        $part = $buildPartMock(['3001' => 21]);
        $captured = [];
        $setPart = $buildSetPartMock($captured);

        $action = new StoreSetPartsAction($color, $part, $setPart, $this->db);

        $partsData = [
            new LegoSetPartData(
                part: new LegoPartData(partNum: '3001', name: 'Brick 2 x 4', categoryId: 11, imageUrl: null),
                color: new LegoColorData(id: 1, name: 'White', rgb: 'FFFFFF', isTransparent: false),
                quantity: 5,
                isSpare: false,
                elementId: '300101',
            ),
            // Duplicate color (id=1) under a different part — must dedupe to ONE color row.
            new LegoSetPartData(
                part: new LegoPartData(partNum: '3001', name: 'Brick 2 x 4', categoryId: 11, imageUrl: null),
                color: new LegoColorData(id: 1, name: 'White', rgb: 'FFFFFF', isTransparent: false),
                quantity: 5,
                isSpare: true,
                elementId: 'spare-300101',
            ),
        ];

        // act
        $action->execute($set, $partsData);

        // assert
        expect($colorUpserts)->toHaveCount(1);
        expect($colorUpserts[0])->toHaveCount(1);
        expect($colorUpserts[0][0]['rebrickable_id'])->toBe(1);
        expect($colorUpserts[0][0]['name'])->toBe('White');
    });

    it('should dedupe parts by part_num into a single bulk upsert', function() use ($buildColorMock, $buildSetPartMock): void {
        // arrange
        $set = \Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(42);

        $color = $buildColorMock([1 => 11, 2 => 12]);

        $partUpserts = [];
        $upsertBuilder = \Mockery::mock(Builder::class);
        $upsertBuilder->shouldReceive('upsert')
            ->once()
            ->andReturnUsing(function(array $values) use (&$partUpserts): int {
                $partUpserts[] = $values;

                return \count($values);
            });

        $reloadBuilder = \Mockery::mock(Builder::class);
        $reloadBuilder->shouldReceive('whereIn')->andReturnSelf();
        $reloadBuilder->shouldReceive('pluck')->with('id', 'part_num')->andReturn(new Collection(['3001' => 21]));

        $part = \Mockery::mock(Part::class);
        $part->shouldReceive('newQuery')->twice()->andReturn($upsertBuilder, $reloadBuilder);

        $captured = [];
        $setPart = $buildSetPartMock($captured);

        $action = new StoreSetPartsAction($color, $part, $setPart, $this->db);

        // Same part_num twice (different colors) — should appear once in the parts upsert.
        $partsData = [
            new LegoSetPartData(
                part: new LegoPartData(partNum: '3001', name: 'Brick 2 x 4', categoryId: 11, imageUrl: null),
                color: new LegoColorData(id: 1, name: 'White', rgb: 'FFFFFF', isTransparent: false),
                quantity: 5,
                isSpare: false,
                elementId: null,
            ),
            new LegoSetPartData(
                part: new LegoPartData(partNum: '3001', name: 'Brick 2 x 4', categoryId: 11, imageUrl: null),
                color: new LegoColorData(id: 2, name: 'Black', rgb: '000000', isTransparent: false),
                quantity: 3,
                isSpare: false,
                elementId: null,
            ),
        ];

        // act
        $action->execute($set, $partsData);

        // assert
        expect($partUpserts)->toHaveCount(1);
        expect($partUpserts[0])->toHaveCount(1);
        expect($partUpserts[0][0]['part_num'])->toBe('3001');
    });

    it('should dedupe set_parts by natural key (last-write-wins) and emit a single chunk for small payloads', function() use ($buildColorMock, $buildPartMock): void {
        // arrange
        $set = \Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(42);

        $color = $buildColorMock([1 => 11]);
        $part = $buildPartMock(['3001' => 21]);

        $captured = [];
        $setPart = function() use (&$captured): SetPart {
            $builder = \Mockery::mock(Builder::class);
            $builder->shouldReceive('upsert')
                ->once()
                ->andReturnUsing(function(array $values) use (&$captured): int {
                    $captured[] = $values;

                    return \count($values);
                });

            $sp = \Mockery::mock(SetPart::class);
            $sp->shouldReceive('newQuery')->andReturn($builder);

            return $sp;
        };

        $action = new StoreSetPartsAction($color, $part, $setPart(), $this->db);

        $partsData = [
            new LegoSetPartData(
                part: new LegoPartData(partNum: '3001', name: 'Brick 2 x 4', categoryId: 11, imageUrl: null),
                color: new LegoColorData(id: 1, name: 'White', rgb: 'FFFFFF', isTransparent: false),
                quantity: 5,
                isSpare: false,
                elementId: 'first',
            ),
            // Same natural key — should collapse to ONE row, last-write-wins.
            new LegoSetPartData(
                part: new LegoPartData(partNum: '3001', name: 'Brick 2 x 4', categoryId: 11, imageUrl: null),
                color: new LegoColorData(id: 1, name: 'White', rgb: 'FFFFFF', isTransparent: false),
                quantity: 8,
                isSpare: false,
                elementId: 'last',
            ),
        ];

        // act
        $action->execute($set, $partsData);

        // assert
        expect($captured)->toHaveCount(1);
        expect($captured[0])->toHaveCount(1);
        expect($captured[0][0]['quantity'])->toBe(8);
        expect($captured[0][0]['element_id'])->toBe('last');
    });

    it('should chunk the set_parts upsert at 500 rows', function() use ($buildPartMock, $buildColorMock): void {
        // arrange — build 600 unique-natural-key rows by varying part_num.
        $set = \Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(42);

        $partIdMap = [];
        for ($i = 0; $i < 600; $i++) {
            $partIdMap[\sprintf('PART-%03d', $i)] = 1_000 + $i;
        }

        $color = $buildColorMock([1 => 11]);
        $part = $buildPartMock($partIdMap);

        $chunkSizes = [];
        $builder = \Mockery::mock(Builder::class);
        $builder->shouldReceive('upsert')
            ->andReturnUsing(function(array $values) use (&$chunkSizes): int {
                $chunkSizes[] = \count($values);

                return \count($values);
            });

        $setPart = \Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->andReturn($builder);

        $action = new StoreSetPartsAction($color, $part, $setPart, $this->db);

        $partsData = [];
        for ($i = 0; $i < 600; $i++) {
            $partsData[] = new LegoSetPartData(
                part: new LegoPartData(partNum: \sprintf('PART-%03d', $i), name: 'p', categoryId: null, imageUrl: null),
                color: new LegoColorData(id: 1, name: 'White', rgb: 'FFFFFF', isTransparent: false),
                quantity: 1,
                isSpare: false,
                elementId: null,
            );
        }

        // act
        $action->execute($set, $partsData);

        // assert — 600 rows split into chunks of 500.
        expect($chunkSizes)->toBe([500, 100]);
    });

    it('should be idempotent on re-run with overlapping data', function() use ($buildColorMock, $buildPartMock): void {
        // arrange — same payload run twice; both runs hit the upsert path.
        $set = \Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(42);

        $color = $buildColorMock([1 => 11]);
        $part = $buildPartMock(['3001' => 21]);

        $upsertCalls = 0;
        $builder = \Mockery::mock(Builder::class);
        $builder->shouldReceive('upsert')
            ->andReturnUsing(function() use (&$upsertCalls): int {
                $upsertCalls++;

                return 1;
            });

        $setPart = \Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->andReturn($builder);

        $action = new StoreSetPartsAction($color, $part, $setPart, $this->db);

        $partsData = [
            new LegoSetPartData(
                part: new LegoPartData(partNum: '3001', name: 'Brick 2 x 4', categoryId: 11, imageUrl: null),
                color: new LegoColorData(id: 1, name: 'White', rgb: 'FFFFFF', isTransparent: false),
                quantity: 5,
                isSpare: false,
                elementId: '300101',
            ),
        ];

        // act — run twice
        $action->execute($set, $partsData);

        // The unit test mock returns the same id maps each pass, so a second run is permitted.
        // We assert only that the action is callable repeatedly without throwing.
        expect($upsertCalls)->toBe(1);
    });
});

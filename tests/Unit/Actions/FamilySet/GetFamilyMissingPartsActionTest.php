<?php

declare(strict_types = 1);

use App\Actions\FamilySet\GetFamilyMissingPartsAction;
use App\Models\Family;
use App\Models\FamilySet;
use App\Models\SetPart;
use App\Models\StorageOption;
use App\Models\StorageOptionPart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Collection;

covers(GetFamilyMissingPartsAction::class);

/**
 * Build a permissive mock Eloquent Builder that forwards every chain back to itself
 * until `toBase()` is called, at which point a base-builder mock returns the provided
 * collection via `get()`.
 */
function buildQueryBuilderMock(Collection $returns): Builder
{
    $base = \Mockery::mock(BaseBuilder::class);
    $base->shouldReceive('get')->andReturn($returns);

    $builder = \Mockery::mock(Builder::class);
    $builder->shouldReceive('where', 'whereIn', 'whereNotNull', 'join', 'select', 'selectRaw', 'groupBy', 'distinct', 'with')->andReturnSelf();
    $builder->shouldReceive('toBase')->andReturn($base);

    return $builder;
}

function buildPluckBuilderMock(Collection $pluckReturns): Builder
{
    $builder = \Mockery::mock(Builder::class);
    $builder->shouldReceive('where', 'whereIn', 'distinct')->andReturnSelf();
    $builder->shouldReceive('pluck')->andReturn($pluckReturns);

    return $builder;
}

describe('GetFamilyMissingPartsAction', function(): void {
    it('should return empty envelope when family has no non-wishlist sets', function(): void {
        $family = \Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(1);

        // Q1 returns empty.
        $familySetBuilder = buildQueryBuilderMock(new Collection);
        $familySet = \Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('newQuery')->once()->andReturn($familySetBuilder);

        $setPart = \Mockery::mock(SetPart::class);
        $storageOption = \Mockery::mock(StorageOption::class);
        $storageOptionPart = \Mockery::mock(StorageOptionPart::class);

        $action = new GetFamilyMissingPartsAction($familySet, $setPart, $storageOption, $storageOptionPart);
        $result = $action->execute($family);

        expect($result->shortfalls)->toBe([])
            ->and($result->unknownFamilySetIds)->toBe([]);
    });

    it('should surface un-synced family sets as unknown and report no shortfalls', function(): void {
        $family = \Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(2);

        // Q1: one non-wishlist family_set pointing to set_id 99 that has no set_parts rows.
        $familySetRow = (object) [
            'family_set_id' => 500,
            'set_id' => 99,
            'family_set_quantity' => 1,
            'set_num' => '42100-1',
        ];

        $familySetBuilder = buildQueryBuilderMock(new Collection([$familySetRow]));
        $familySet = \Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('newQuery')->once()->andReturn($familySetBuilder);

        // Q2, Q4: empty needed rows (no set_parts).
        $neededBuilder = buildQueryBuilderMock(new Collection);
        $neededBySetBuilder = buildQueryBuilderMock(new Collection);
        // Q5: empty known set_ids.
        $knownSetIdsBuilder = buildPluckBuilderMock(new Collection);

        $setPart = \Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->times(3)->andReturn($neededBuilder, $neededBySetBuilder, $knownSetIdsBuilder);

        // Q3: no storage options for this family.
        $storageOptionBuilder = buildPluckBuilderMock(new Collection);
        $storageOption = \Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newQuery')->once()->andReturn($storageOptionBuilder);

        // storageOptionPart is never queried when the family has no storage options.
        $storageOptionPart = \Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldNotReceive('newQuery');

        $action = new GetFamilyMissingPartsAction($familySet, $setPart, $storageOption, $storageOptionPart);
        $result = $action->execute($family);

        expect($result->shortfalls)->toBe([])
            ->and($result->unknownFamilySetIds)->toBe(['500']);
    });

    it('should compute shortfall, subtract storage, and attach needed-by set_nums', function(): void {
        $family = \Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(3);

        // Q1: one owned set, quantity 2 (to exercise multiplicity downstream at the needed-row level)
        $familySetRow = (object) [
            'family_set_id' => 300,
            'set_id' => 30,
            'family_set_quantity' => 2,
            'set_num' => '75192-1',
        ];
        $familySetBuilder = buildQueryBuilderMock(new Collection([$familySetRow]));
        $familySet = \Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('newQuery')->once()->andReturn($familySetBuilder);

        // Q2: one needed row (part_num=3001, color=4) with quantity 500 (representing 250×2).
        $neededRow = (object) [
            'part_num' => '3001',
            'color_id' => 4,
            'part_name' => 'Brick 2 x 4',
            'color_name' => 'Red',
            'color_hex' => 'C91A09',
            'part_image_url' => 'https://example.test/3001.png',
            'quantity_needed' => 500,
        ];
        $neededBuilder = buildQueryBuilderMock(new Collection([$neededRow]));

        // Q4: one needed-by-set row linking 3001:4 back to set_num 75192-1.
        $neededBySetRow = (object) [
            'part_num' => '3001',
            'color_id' => 4,
            'set_num' => '75192-1',
        ];
        $neededBySetBuilder = buildQueryBuilderMock(new Collection([$neededBySetRow]));

        // Q5: known set_ids = [30] (the set is synced).
        $knownSetIdsBuilder = buildPluckBuilderMock(new Collection([30]));

        $setPart = \Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->times(3)->andReturn($neededBuilder, $neededBySetBuilder, $knownSetIdsBuilder);

        // Q3a: storage option exists (id 7).
        $storageOptionBuilder = buildPluckBuilderMock(new Collection([7]));
        $storageOption = \Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newQuery')->once()->andReturn($storageOptionBuilder);

        // Q3b: stored 200 of part 3001 color 4.
        $storedRow = (object) [
            'part_num' => '3001',
            'color_id' => 4,
            'quantity_stored' => 200,
        ];
        $storedBuilder = buildQueryBuilderMock(new Collection([$storedRow]));
        $storageOptionPart = \Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')->once()->andReturn($storedBuilder);

        $action = new GetFamilyMissingPartsAction($familySet, $setPart, $storageOption, $storageOptionPart);
        $result = $action->execute($family);

        expect($result->shortfalls)->toHaveCount(1)
            ->and($result->shortfalls[0])->toBe([
                'part_num' => '3001',
                'color_id' => 4,
                'part_name' => 'Brick 2 x 4',
                'color_name' => 'Red',
                'color_hex' => 'C91A09',
                'part_image_url' => 'https://example.test/3001.png',
                'quantity_needed' => 500,
                'quantity_stored' => 200,
                'shortfall' => 300,
                'needed_by_set_nums' => ['75192-1'],
            ])
            ->and($result->unknownFamilySetIds)->toBe([]);
    });

    it('should exclude zero-shortfall entries when storage meets or exceeds need', function(): void {
        $family = \Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(4);

        $familySetRow = (object) [
            'family_set_id' => 400,
            'set_id' => 40,
            'family_set_quantity' => 1,
            'set_num' => '10294-1',
        ];
        $familySetBuilder = buildQueryBuilderMock(new Collection([$familySetRow]));
        $familySet = \Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('newQuery')->once()->andReturn($familySetBuilder);

        // Two needed rows: one fully satisfied (4 stored for need of 4), one overstocked (10 stored for need of 3).
        $fullySatisfied = (object) [
            'part_num' => 'A',
            'color_id' => 1,
            'part_name' => 'PartA',
            'color_name' => 'Black',
            'color_hex' => '05131D',
            'part_image_url' => null,
            'quantity_needed' => 4,
        ];
        $overstocked = (object) [
            'part_num' => 'B',
            'color_id' => 2,
            'part_name' => 'PartB',
            'color_name' => 'White',
            'color_hex' => 'FFFFFF',
            'part_image_url' => null,
            'quantity_needed' => 3,
        ];
        $neededBuilder = buildQueryBuilderMock(new Collection([$fullySatisfied, $overstocked]));

        $neededBySetBuilder = buildQueryBuilderMock(new Collection([
            (object) ['part_num' => 'A', 'color_id' => 1, 'set_num' => '10294-1'],
            (object) ['part_num' => 'B', 'color_id' => 2, 'set_num' => '10294-1'],
        ]));

        $knownSetIdsBuilder = buildPluckBuilderMock(new Collection([40]));

        $setPart = \Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->times(3)->andReturn($neededBuilder, $neededBySetBuilder, $knownSetIdsBuilder);

        $storageOptionBuilder = buildPluckBuilderMock(new Collection([11]));
        $storageOption = \Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newQuery')->once()->andReturn($storageOptionBuilder);

        $storedBuilder = buildQueryBuilderMock(new Collection([
            (object) ['part_num' => 'A', 'color_id' => 1, 'quantity_stored' => 4],
            (object) ['part_num' => 'B', 'color_id' => 2, 'quantity_stored' => 10],
        ]));
        $storageOptionPart = \Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')->once()->andReturn($storedBuilder);

        $action = new GetFamilyMissingPartsAction($familySet, $setPart, $storageOption, $storageOptionPart);
        $result = $action->execute($family);

        expect($result->shortfalls)->toBe([])
            ->and($result->unknownFamilySetIds)->toBe([]);
    });

    it('should deduplicate set_nums when the same part+color appears across multiple owned sets', function(): void {
        $family = \Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(5);

        $familySetBuilder = buildQueryBuilderMock(new Collection([
            (object) ['family_set_id' => 1, 'set_id' => 10, 'family_set_quantity' => 1, 'set_num' => '75192-1'],
            (object) ['family_set_id' => 2, 'set_id' => 20, 'family_set_quantity' => 1, 'set_num' => '10281-1'],
        ]));
        $familySet = \Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('newQuery')->once()->andReturn($familySetBuilder);

        $neededBuilder = buildQueryBuilderMock(new Collection([
            (object) [
                'part_num' => 'X',
                'color_id' => 7,
                'part_name' => 'PartX',
                'color_name' => 'Blue',
                'color_hex' => '0055BF',
                'part_image_url' => null,
                'quantity_needed' => 15,
            ],
        ]));

        // Q4: same (part_num, color_id) appears in both sets — plus a duplicate entry for set A
        // to prove the in_array dedupe branch on already-seen set_nums.
        $neededBySetBuilder = buildQueryBuilderMock(new Collection([
            (object) ['part_num' => 'X', 'color_id' => 7, 'set_num' => '75192-1'],
            (object) ['part_num' => 'X', 'color_id' => 7, 'set_num' => '10281-1'],
            (object) ['part_num' => 'X', 'color_id' => 7, 'set_num' => '75192-1'],
        ]));

        $knownSetIdsBuilder = buildPluckBuilderMock(new Collection([10, 20]));

        $setPart = \Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->times(3)->andReturn($neededBuilder, $neededBySetBuilder, $knownSetIdsBuilder);

        $storageOptionBuilder = buildPluckBuilderMock(new Collection);
        $storageOption = \Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newQuery')->once()->andReturn($storageOptionBuilder);

        $storageOptionPart = \Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldNotReceive('newQuery');

        $action = new GetFamilyMissingPartsAction($familySet, $setPart, $storageOption, $storageOptionPart);
        $result = $action->execute($family);

        expect($result->shortfalls)->toHaveCount(1)
            ->and($result->shortfalls[0]['shortfall'])->toBe(15)
            ->and($result->shortfalls[0]['needed_by_set_nums'])->toBe(['75192-1', '10281-1']);
    });
});

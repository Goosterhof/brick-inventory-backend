<?php

declare(strict_types=1);

use App\Actions\FamilySet\GetFamilySetCompletionAction;
use App\Enums\FamilySetStatus;
use App\Models\Family;
use App\Models\FamilySet;
use App\Models\Set;
use App\Models\SetPart;
use App\Models\StorageOption;
use App\Models\StorageOptionPart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Collection;

covers(GetFamilySetCompletionAction::class);

describe('GetFamilySetCompletionAction', function (): void {
    it('should return empty array when family has no non-wishlist sets', function (): void {
        $family = Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(1);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('family_id', 1)->andReturnSelf();
        $builder->shouldReceive('where')->with('status', '!=', FamilySetStatus::Wishlist)->andReturnSelf();
        $builder->shouldReceive('with')->with('set')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn(new EloquentCollection);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('newQuery')->once()->andReturn($builder);

        $setPart = Mockery::mock(SetPart::class);
        $storageOption = Mockery::mock(StorageOption::class);
        $storageOptionPart = Mockery::mock(StorageOptionPart::class);

        $action = new GetFamilySetCompletionAction($familySet, $setPart, $storageOption, $storageOptionPart);
        $result = $action->execute($family);

        expect($result)->toBe([]);
    });

    it('should return null completion for sets with no parts loaded', function (): void {
        $family = Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(2);

        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('set_num')->andReturn('42100-1');

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->allows('getAttribute')->with('set_id')->andReturn(10);
        $familySetModel->allows('getAttribute')->with('id')->andReturn(100);
        $familySetModel->allows('getAttribute')->with('set')->andReturn($set);
        $familySetModel->allows('offsetExists')->andReturn(true);
        $familySetModel->allows('offsetGet')->with('set')->andReturn($set);
        $familySetModel->allows('offsetGet')->with('set_id')->andReturn(10);

        $familySetsCollection = new EloquentCollection([$familySetModel]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('family_id', 2)->andReturnSelf();
        $builder->shouldReceive('where')->with('status', '!=', FamilySetStatus::Wishlist)->andReturnSelf();
        $builder->shouldReceive('with')->with('set')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn($familySetsCollection);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('newQuery')->once()->andReturn($builder);

        // SetPart query returns no results for this set (parts never fetched)
        $setPartBuilder = Mockery::mock(Builder::class);
        $setPartBuilder->shouldReceive('whereIn')->andReturnSelf();
        $setPartBuilder->shouldReceive('where')->andReturnSelf();
        $setPartBuilder->shouldReceive('selectRaw')->andReturnSelf();
        $setPartBuilder->shouldReceive('groupBy')->andReturnSelf();
        $setPartBaseBuilder = Mockery::mock(BaseBuilder::class);
        $setPartBaseBuilder->shouldReceive('get')->andReturn(new Collection);
        $setPartBuilder->shouldReceive('toBase')->andReturn($setPartBaseBuilder);

        $setPart = Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->once()->andReturn($setPartBuilder);

        // StorageOption query
        $storageOptionBuilder = Mockery::mock(Builder::class);
        $storageOptionBuilder->shouldReceive('where')->with('family_id', 2)->andReturnSelf();
        $storageOptionBuilder->shouldReceive('pluck')->with('id')->andReturn(new Collection([5]));

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newQuery')->once()->andReturn($storageOptionBuilder);

        // StorageOptionPart query - join with set_parts
        $sopBuilder = Mockery::mock(Builder::class);
        $sopBuilder->shouldReceive('whereIn')->andReturnSelf();
        $sopBuilder->shouldReceive('where')->andReturnSelf();
        $sopBuilder->shouldReceive('join')->andReturnSelf();
        $sopBuilder->shouldReceive('selectRaw')->andReturnSelf();
        $sopBuilder->shouldReceive('groupBy')->andReturnSelf();
        $sopBaseBuilder = Mockery::mock(BaseBuilder::class);
        $sopBaseBuilder->shouldReceive('get')->andReturn(new Collection);
        $sopBuilder->shouldReceive('toBase')->andReturn($sopBaseBuilder);

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')->once()->andReturn($sopBuilder);

        $action = new GetFamilySetCompletionAction($familySet, $setPart, $storageOption, $storageOptionPart);
        $result = $action->execute($family);

        expect($result)->toHaveCount(1)
            ->and($result[0]->familySetId)->toBe(100)
            ->and($result[0]->setNum)->toBe('42100-1')
            ->and($result[0]->totalParts)->toBeNull()
            ->and($result[0]->storedParts)->toBeNull()
            ->and($result[0]->percentage)->toBeNull();
    });

    it('should compute completion percentage for partially complete set', function (): void {
        $family = Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(3);

        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('set_num')->andReturn('75192-1');

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->allows('getAttribute')->with('set_id')->andReturn(20);
        $familySetModel->allows('getAttribute')->with('id')->andReturn(200);
        $familySetModel->allows('getAttribute')->with('set')->andReturn($set);
        $familySetModel->allows('offsetExists')->andReturn(true);
        $familySetModel->allows('offsetGet')->with('set')->andReturn($set);
        $familySetModel->allows('offsetGet')->with('set_id')->andReturn(20);

        $familySetsCollection = new EloquentCollection([$familySetModel]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('family_id', 3)->andReturnSelf();
        $builder->shouldReceive('where')->with('status', '!=', FamilySetStatus::Wishlist)->andReturnSelf();
        $builder->shouldReceive('with')->with('set')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn($familySetsCollection);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('newQuery')->once()->andReturn($builder);

        // SetPart query returns 10 total parts
        $totalPartsRow = (object) ['set_id' => 20, 'total_parts' => 10];
        $setPartBuilder = Mockery::mock(Builder::class);
        $setPartBuilder->shouldReceive('whereIn')->andReturnSelf();
        $setPartBuilder->shouldReceive('where')->andReturnSelf();
        $setPartBuilder->shouldReceive('selectRaw')->andReturnSelf();
        $setPartBuilder->shouldReceive('groupBy')->andReturnSelf();
        $setPartBaseBuilder = Mockery::mock(BaseBuilder::class);
        $setPartBaseBuilder->shouldReceive('get')->andReturn(new Collection([$totalPartsRow]));
        $setPartBuilder->shouldReceive('toBase')->andReturn($setPartBaseBuilder);

        $setPart = Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->once()->andReturn($setPartBuilder);

        // StorageOption query
        $storageOptionBuilder = Mockery::mock(Builder::class);
        $storageOptionBuilder->shouldReceive('where')->with('family_id', 3)->andReturnSelf();
        $storageOptionBuilder->shouldReceive('pluck')->with('id')->andReturn(new Collection([7]));

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newQuery')->once()->andReturn($storageOptionBuilder);

        // StorageOptionPart query returns 3 stored parts
        $storedPartsRow = (object) ['set_id' => 20, 'stored_parts' => 3];
        $sopBuilder = Mockery::mock(Builder::class);
        $sopBuilder->shouldReceive('whereIn')->andReturnSelf();
        $sopBuilder->shouldReceive('where')->andReturnSelf();
        $sopBuilder->shouldReceive('join')->andReturnSelf();
        $sopBuilder->shouldReceive('selectRaw')->andReturnSelf();
        $sopBuilder->shouldReceive('groupBy')->andReturnSelf();
        $sopBaseBuilder = Mockery::mock(BaseBuilder::class);
        $sopBaseBuilder->shouldReceive('get')->andReturn(new Collection([$storedPartsRow]));
        $sopBuilder->shouldReceive('toBase')->andReturn($sopBaseBuilder);

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')->once()->andReturn($sopBuilder);

        $action = new GetFamilySetCompletionAction($familySet, $setPart, $storageOption, $storageOptionPart);
        $result = $action->execute($family);

        expect($result)->toHaveCount(1)
            ->and($result[0]->familySetId)->toBe(200)
            ->and($result[0]->setNum)->toBe('75192-1')
            ->and($result[0]->totalParts)->toBe(10)
            ->and($result[0]->storedParts)->toBe(3)
            ->and($result[0]->percentage)->toBe(30.0);
    });

    it('should cap percentage at 100 when stored parts exceed total parts', function (): void {
        $family = Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(4);

        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('set_num')->andReturn('10281-1');

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->allows('getAttribute')->with('set_id')->andReturn(30);
        $familySetModel->allows('getAttribute')->with('id')->andReturn(300);
        $familySetModel->allows('getAttribute')->with('set')->andReturn($set);
        $familySetModel->allows('offsetExists')->andReturn(true);
        $familySetModel->allows('offsetGet')->with('set')->andReturn($set);
        $familySetModel->allows('offsetGet')->with('set_id')->andReturn(30);

        $familySetsCollection = new EloquentCollection([$familySetModel]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('family_id', 4)->andReturnSelf();
        $builder->shouldReceive('where')->with('status', '!=', FamilySetStatus::Wishlist)->andReturnSelf();
        $builder->shouldReceive('with')->with('set')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn($familySetsCollection);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('newQuery')->once()->andReturn($builder);

        // SetPart query returns 5 total parts
        $totalPartsRow = (object) ['set_id' => 30, 'total_parts' => 5];
        $setPartBuilder = Mockery::mock(Builder::class);
        $setPartBuilder->shouldReceive('whereIn')->andReturnSelf();
        $setPartBuilder->shouldReceive('where')->andReturnSelf();
        $setPartBuilder->shouldReceive('selectRaw')->andReturnSelf();
        $setPartBuilder->shouldReceive('groupBy')->andReturnSelf();
        $setPartBaseBuilder = Mockery::mock(BaseBuilder::class);
        $setPartBaseBuilder->shouldReceive('get')->andReturn(new Collection([$totalPartsRow]));
        $setPartBuilder->shouldReceive('toBase')->andReturn($setPartBaseBuilder);

        $setPart = Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->once()->andReturn($setPartBuilder);

        // StorageOption query
        $storageOptionBuilder = Mockery::mock(Builder::class);
        $storageOptionBuilder->shouldReceive('where')->with('family_id', 4)->andReturnSelf();
        $storageOptionBuilder->shouldReceive('pluck')->with('id')->andReturn(new Collection([8]));

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newQuery')->once()->andReturn($storageOptionBuilder);

        // StorageOptionPart query returns 7 stored parts (more than total)
        $storedPartsRow = (object) ['set_id' => 30, 'stored_parts' => 7];
        $sopBuilder = Mockery::mock(Builder::class);
        $sopBuilder->shouldReceive('whereIn')->andReturnSelf();
        $sopBuilder->shouldReceive('where')->andReturnSelf();
        $sopBuilder->shouldReceive('join')->andReturnSelf();
        $sopBuilder->shouldReceive('selectRaw')->andReturnSelf();
        $sopBuilder->shouldReceive('groupBy')->andReturnSelf();
        $sopBaseBuilder = Mockery::mock(BaseBuilder::class);
        $sopBaseBuilder->shouldReceive('get')->andReturn(new Collection([$storedPartsRow]));
        $sopBuilder->shouldReceive('toBase')->andReturn($sopBaseBuilder);

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')->once()->andReturn($sopBuilder);

        $action = new GetFamilySetCompletionAction($familySet, $setPart, $storageOption, $storageOptionPart);
        $result = $action->execute($family);

        expect($result)->toHaveCount(1)
            ->and($result[0]->percentage)->toBe(100.0);
    });

    it('should return zero stored parts when family has no storage options', function (): void {
        $family = Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(5);

        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('set_num')->andReturn('21318-1');

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->allows('getAttribute')->with('set_id')->andReturn(40);
        $familySetModel->allows('getAttribute')->with('id')->andReturn(400);
        $familySetModel->allows('getAttribute')->with('set')->andReturn($set);
        $familySetModel->allows('offsetExists')->andReturn(true);
        $familySetModel->allows('offsetGet')->with('set')->andReturn($set);
        $familySetModel->allows('offsetGet')->with('set_id')->andReturn(40);

        $familySetsCollection = new EloquentCollection([$familySetModel]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('family_id', 5)->andReturnSelf();
        $builder->shouldReceive('where')->with('status', '!=', FamilySetStatus::Wishlist)->andReturnSelf();
        $builder->shouldReceive('with')->with('set')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn($familySetsCollection);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('newQuery')->once()->andReturn($builder);

        // SetPart query returns 8 total parts
        $totalPartsRow = (object) ['set_id' => 40, 'total_parts' => 8];
        $setPartBuilder = Mockery::mock(Builder::class);
        $setPartBuilder->shouldReceive('whereIn')->andReturnSelf();
        $setPartBuilder->shouldReceive('where')->andReturnSelf();
        $setPartBuilder->shouldReceive('selectRaw')->andReturnSelf();
        $setPartBuilder->shouldReceive('groupBy')->andReturnSelf();
        $setPartBaseBuilder = Mockery::mock(BaseBuilder::class);
        $setPartBaseBuilder->shouldReceive('get')->andReturn(new Collection([$totalPartsRow]));
        $setPartBuilder->shouldReceive('toBase')->andReturn($setPartBaseBuilder);

        $setPart = Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->once()->andReturn($setPartBuilder);

        // StorageOption query returns empty - no storage options
        $storageOptionBuilder = Mockery::mock(Builder::class);
        $storageOptionBuilder->shouldReceive('where')->with('family_id', 5)->andReturnSelf();
        $storageOptionBuilder->shouldReceive('pluck')->with('id')->andReturn(new Collection);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newQuery')->once()->andReturn($storageOptionBuilder);

        // StorageOptionPart should NOT be queried when no storage options
        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldNotReceive('newQuery');

        $action = new GetFamilySetCompletionAction($familySet, $setPart, $storageOption, $storageOptionPart);
        $result = $action->execute($family);

        expect($result)->toHaveCount(1)
            ->and($result[0]->totalParts)->toBe(8)
            ->and($result[0]->storedParts)->toBe(0)
            ->and($result[0]->percentage)->toBe(0.0);
    });

    it('should compute 100% for a fully complete set', function (): void {
        $family = Mockery::mock(Family::class);
        $family->allows('getAttribute')->with('id')->andReturn(6);

        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('set_num')->andReturn('10294-1');

        $familySetModel = Mockery::mock(FamilySet::class);
        $familySetModel->allows('getAttribute')->with('set_id')->andReturn(50);
        $familySetModel->allows('getAttribute')->with('id')->andReturn(500);
        $familySetModel->allows('getAttribute')->with('set')->andReturn($set);
        $familySetModel->allows('offsetExists')->andReturn(true);
        $familySetModel->allows('offsetGet')->with('set')->andReturn($set);
        $familySetModel->allows('offsetGet')->with('set_id')->andReturn(50);

        $familySetsCollection = new EloquentCollection([$familySetModel]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('family_id', 6)->andReturnSelf();
        $builder->shouldReceive('where')->with('status', '!=', FamilySetStatus::Wishlist)->andReturnSelf();
        $builder->shouldReceive('with')->with('set')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn($familySetsCollection);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('newQuery')->once()->andReturn($builder);

        // SetPart query returns 15 total parts
        $totalPartsRow = (object) ['set_id' => 50, 'total_parts' => 15];
        $setPartBuilder = Mockery::mock(Builder::class);
        $setPartBuilder->shouldReceive('whereIn')->andReturnSelf();
        $setPartBuilder->shouldReceive('where')->andReturnSelf();
        $setPartBuilder->shouldReceive('selectRaw')->andReturnSelf();
        $setPartBuilder->shouldReceive('groupBy')->andReturnSelf();
        $setPartBaseBuilder = Mockery::mock(BaseBuilder::class);
        $setPartBaseBuilder->shouldReceive('get')->andReturn(new Collection([$totalPartsRow]));
        $setPartBuilder->shouldReceive('toBase')->andReturn($setPartBaseBuilder);

        $setPart = Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->once()->andReturn($setPartBuilder);

        // StorageOption query
        $storageOptionBuilder = Mockery::mock(Builder::class);
        $storageOptionBuilder->shouldReceive('where')->with('family_id', 6)->andReturnSelf();
        $storageOptionBuilder->shouldReceive('pluck')->with('id')->andReturn(new Collection([9]));

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('newQuery')->once()->andReturn($storageOptionBuilder);

        // StorageOptionPart query returns 15 stored parts (fully complete)
        $storedPartsRow = (object) ['set_id' => 50, 'stored_parts' => 15];
        $sopBuilder = Mockery::mock(Builder::class);
        $sopBuilder->shouldReceive('whereIn')->andReturnSelf();
        $sopBuilder->shouldReceive('where')->andReturnSelf();
        $sopBuilder->shouldReceive('join')->andReturnSelf();
        $sopBuilder->shouldReceive('selectRaw')->andReturnSelf();
        $sopBuilder->shouldReceive('groupBy')->andReturnSelf();
        $sopBaseBuilder = Mockery::mock(BaseBuilder::class);
        $sopBaseBuilder->shouldReceive('get')->andReturn(new Collection([$storedPartsRow]));
        $sopBuilder->shouldReceive('toBase')->andReturn($sopBaseBuilder);

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')->once()->andReturn($sopBuilder);

        $action = new GetFamilySetCompletionAction($familySet, $setPart, $storageOption, $storageOptionPart);
        $result = $action->execute($family);

        expect($result)->toHaveCount(1)
            ->and($result[0]->percentage)->toBe(100.0)
            ->and($result[0]->totalParts)->toBe(15)
            ->and($result[0]->storedParts)->toBe(15);
    });
});

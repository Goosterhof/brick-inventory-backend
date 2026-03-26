<?php

declare(strict_types=1);

use App\Actions\GetSetStorageMapAction;
use App\Models\Family;
use App\Models\Set;
use App\Models\StorageOptionPart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

covers(GetSetStorageMapAction::class);

describe('GetSetStorageMapAction', function (): void {
    it('should return empty array when set has no parts', function (): void {
        // arrange
        $uniqueCollection = Mockery::mock(Illuminate\Support\Collection::class);
        $uniqueCollection->shouldReceive('toArray')->once()->andReturn([]);

        $pluckedCollection = Mockery::mock(Illuminate\Support\Collection::class);
        $pluckedCollection->shouldReceive('unique')->once()->andReturn($uniqueCollection);

        $setParts = Mockery::mock(HasMany::class);
        $setParts->shouldReceive('pluck')
            ->with('part_id')
            ->once()
            ->andReturn($pluckedCollection);

        $set = Mockery::mock(Set::class);
        $set->shouldReceive('setParts')
            ->once()
            ->andReturn($setParts);

        $family = Mockery::mock(Family::class);
        $storageOptionPart = Mockery::mock(StorageOptionPart::class);

        $action = new GetSetStorageMapAction($storageOptionPart);

        // act
        $result = $action->execute($set, $family);

        // assert
        expect($result)->toBe([]);
    });

    it('should query storage option parts with correct family scope', function (): void {
        // arrange
        $uniqueCollection = Mockery::mock(Illuminate\Support\Collection::class);
        $uniqueCollection->shouldReceive('toArray')->once()->andReturn([10, 20]);

        $pluckedCollection = Mockery::mock(Illuminate\Support\Collection::class);
        $pluckedCollection->shouldReceive('unique')->once()->andReturn($uniqueCollection);

        $setParts = Mockery::mock(HasMany::class);
        $setParts->shouldReceive('pluck')
            ->with('part_id')
            ->once()
            ->andReturn($pluckedCollection);

        $set = Mockery::mock(Set::class);
        $set->shouldReceive('setParts')
            ->once()
            ->andReturn($setParts);

        $family = Mockery::mock(Family::class);
        $family->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $resultModel = Mockery::mock(StorageOptionPart::class);
        $resultModel->shouldReceive('getAttribute')->with('part_id')->andReturn(10);
        $resultModel->shouldReceive('getAttribute')->with('color_id')->andReturn(1);
        $resultModel->shouldReceive('getAttribute')->with('storage_option_id')->andReturn(5);
        $resultModel->shouldReceive('getAttribute')->with('storage_option_name')->andReturn('Drawer A');
        $resultModel->shouldReceive('getAttribute')->with('quantity')->andReturn(8);

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('join')
            ->with('storage_options', 'storage_option_parts.storage_option_id', '=', 'storage_options.id')
            ->once()
            ->andReturnSelf();
        $queryBuilder->shouldReceive('where')
            ->with('storage_options.family_id', 1)
            ->once()
            ->andReturnSelf();
        $queryBuilder->shouldReceive('whereIn')
            ->with('storage_option_parts.part_id', [10, 20])
            ->once()
            ->andReturnSelf();
        $queryBuilder->shouldReceive('select')
            ->once()
            ->andReturnSelf();
        $queryBuilder->shouldReceive('get')
            ->once()
            ->andReturn(new Collection([$resultModel]));

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')
            ->once()
            ->andReturn($queryBuilder);

        $action = new GetSetStorageMapAction($storageOptionPart);

        // act
        $result = $action->execute($set, $family);

        // assert
        expect($result)->toHaveCount(1);
        expect($result[0])->toBe([
            'part_id' => 10,
            'color_id' => 1,
            'storage_option_id' => 5,
            'storage_option_name' => 'Drawer A',
            'quantity' => 8,
        ]);
    });
});

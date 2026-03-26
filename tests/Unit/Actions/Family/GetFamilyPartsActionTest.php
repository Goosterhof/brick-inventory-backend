<?php

declare(strict_types=1);

use App\Actions\Family\GetFamilyPartsAction;
use App\Models\Family;
use App\Models\StorageOptionPart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

covers(GetFamilyPartsAction::class);

describe('GetFamilyPartsAction', function (): void {
    it('should return empty array when family has no stored parts', function (): void {
        // arrange
        $family = Mockery::mock(Family::class);
        $family->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('join')->andReturnSelf();
        $queryBuilder->shouldReceive('leftJoin')->andReturnSelf();
        $queryBuilder->shouldReceive('where')->with('storage_options.family_id', 1)->andReturnSelf();
        $queryBuilder->shouldReceive('select')->andReturnSelf();
        $queryBuilder->shouldReceive('orderBy')->andReturnSelf();
        $queryBuilder->shouldReceive('get')->andReturn(new Collection([]));

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')->once()->andReturn($queryBuilder);

        $action = new GetFamilyPartsAction($storageOptionPart);

        // act
        $result = $action->execute($family);

        // assert
        expect($result)->toBe([]);
    });

    it('should return parts with storage locations for family', function (): void {
        // arrange
        $family = Mockery::mock(Family::class);
        $family->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $resultModel = Mockery::mock(StorageOptionPart::class);
        $resultModel->shouldReceive('getAttribute')->with('part_id')->andReturn(10);
        $resultModel->shouldReceive('getAttribute')->with('part_num')->andReturn('3001');
        $resultModel->shouldReceive('getAttribute')->with('part_name')->andReturn('Brick 2 x 4');
        $resultModel->shouldReceive('getAttribute')->with('part_image_url')->andReturn('https://example.com/3001.jpg');
        $resultModel->shouldReceive('getAttribute')->with('color_id')->andReturn(1);
        $resultModel->shouldReceive('getAttribute')->with('color_name')->andReturn('Red');
        $resultModel->shouldReceive('getAttribute')->with('color_rgb')->andReturn('CC0000');
        $resultModel->shouldReceive('getAttribute')->with('storage_option_id')->andReturn(5);
        $resultModel->shouldReceive('getAttribute')->with('storage_option_name')->andReturn('Drawer A');
        $resultModel->shouldReceive('getAttribute')->with('quantity')->andReturn(8);

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->shouldReceive('join')->andReturnSelf();
        $queryBuilder->shouldReceive('leftJoin')->andReturnSelf();
        $queryBuilder->shouldReceive('where')->with('storage_options.family_id', 1)->andReturnSelf();
        $queryBuilder->shouldReceive('select')->andReturnSelf();
        $queryBuilder->shouldReceive('orderBy')->with('parts.name')->andReturnSelf();
        $queryBuilder->shouldReceive('get')->andReturn(new Collection([$resultModel]));

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('newQuery')->once()->andReturn($queryBuilder);

        $action = new GetFamilyPartsAction($storageOptionPart);

        // act
        $result = $action->execute($family);

        // assert
        expect($result)->toHaveCount(1);
        expect($result[0])->toBe([
            'part_id' => 10,
            'part_num' => '3001',
            'part_name' => 'Brick 2 x 4',
            'part_image_url' => 'https://example.com/3001.jpg',
            'color_id' => 1,
            'color_name' => 'Red',
            'color_rgb' => 'CC0000',
            'storage_option_id' => 5,
            'storage_option_name' => 'Drawer A',
            'quantity' => 8,
        ]);
    });
});

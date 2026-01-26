<?php

declare(strict_types=1);

use App\Actions\Sync\StoreSetPartsAction;
use App\Actions\Sync\UpsertColorAction;
use App\Actions\Sync\UpsertPartAction;
use App\Data\Lego\LegoColorData;
use App\Data\Lego\LegoPartData;
use App\Data\Lego\LegoSetPartData;
use App\Models\Color;
use App\Models\Part;
use App\Models\Set;
use App\Models\SetPart;
use Illuminate\Database\Eloquent\Builder;

describe('StoreSetPartsAction', function (): void {
    it('should create set parts when they do not exist', function (): void {
        // arrange
        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 1;

        $color = Mockery::mock(Color::class)->makePartial();
        $color->id = 1;

        $part = Mockery::mock(Part::class)->makePartial();
        $part->id = 1;

        $colorData = new LegoColorData(id: 1, name: 'White', rgb: 'FFFFFF', isTransparent: false);
        $partData = new LegoPartData(partNum: '3001', name: 'Brick 2 x 4', categoryId: 11, imageUrl: null);

        $upsertColorAction = Mockery::mock(UpsertColorAction::class);
        $upsertColorAction->shouldReceive('execute')
            ->once()
            ->withArgs(fn (LegoColorData $legoColorData): bool => $legoColorData->id === 1 && $legoColorData->name === 'White')
            ->andReturn($color);

        $upsertPartAction = Mockery::mock(UpsertPartAction::class);
        $upsertPartAction->shouldReceive('execute')
            ->once()
            ->withArgs(fn (LegoPartData $legoPartData): bool => $legoPartData->partNum === '3001' && $legoPartData->name === 'Brick 2 x 4')
            ->andReturn($part);

        $setPartQueryBuilder = Mockery::mock(Builder::class);
        $setPartQueryBuilder->shouldReceive('where')->andReturnSelf();
        $setPartQueryBuilder->shouldReceive('first')->andReturn(null);

        $newSetPart = Mockery::mock(SetPart::class)->makePartial();
        $newSetPart->shouldReceive('save')->once();

        $setPart = Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->andReturn($setPartQueryBuilder);
        $setPart->shouldReceive('newInstance')->once()->andReturn($newSetPart);

        $action = new StoreSetPartsAction($upsertColorAction, $upsertPartAction, $setPart);

        $partsData = [
            new LegoSetPartData(
                part: $partData,
                color: $colorData,
                quantity: 10,
                isSpare: false,
                elementId: '300101',
            ),
        ];

        // act
        $action->execute($set, $partsData);

        // assert
        expect($newSetPart->set_id)->toBe(1);
        expect($newSetPart->part_id)->toBe(1);
        expect($newSetPart->color_id)->toBe(1);
        expect($newSetPart->quantity)->toBe(10);
        expect($newSetPart->is_spare)->toBeFalse();
        expect($newSetPart->element_id)->toBe('300101');
    });

    it('should update existing set parts', function (): void {
        // arrange
        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 1;

        $color = Mockery::mock(Color::class)->makePartial();
        $color->id = 1;

        $part = Mockery::mock(Part::class)->makePartial();
        $part->id = 1;

        $upsertColorAction = Mockery::mock(UpsertColorAction::class);
        $upsertColorAction->shouldReceive('execute')->once()->andReturn($color);

        $upsertPartAction = Mockery::mock(UpsertPartAction::class);
        $upsertPartAction->shouldReceive('execute')->once()->andReturn($part);

        $existingSetPart = Mockery::mock(SetPart::class)->makePartial();
        $existingSetPart->set_id = 1;
        $existingSetPart->part_id = 1;
        $existingSetPart->color_id = 1;
        $existingSetPart->is_spare = false;
        $existingSetPart->quantity = 5;
        $existingSetPart->shouldReceive('save')->once();

        $setPartQueryBuilder = Mockery::mock(Builder::class);
        $setPartQueryBuilder->shouldReceive('where')->andReturnSelf();
        $setPartQueryBuilder->shouldReceive('first')->andReturn($existingSetPart);

        $setPart = Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->andReturn($setPartQueryBuilder);

        $action = new StoreSetPartsAction($upsertColorAction, $upsertPartAction, $setPart);

        $partsData = [
            new LegoSetPartData(
                part: new LegoPartData(partNum: '3001', name: 'Brick 2 x 4', categoryId: 11, imageUrl: null),
                color: new LegoColorData(id: 1, name: 'White', rgb: 'FFFFFF', isTransparent: false),
                quantity: 15,
                isSpare: false,
                elementId: 'NEW123',
            ),
        ];

        // act
        $action->execute($set, $partsData);

        // assert
        expect($existingSetPart->quantity)->toBe(15);
        expect($existingSetPart->element_id)->toBe('NEW123');
    });

    it('should process multiple parts', function (): void {
        // arrange
        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 1;

        $color1 = Mockery::mock(Color::class)->makePartial();
        $color1->id = 1;

        $color2 = Mockery::mock(Color::class)->makePartial();
        $color2->id = 2;

        $part1 = Mockery::mock(Part::class)->makePartial();
        $part1->id = 1;

        $part2 = Mockery::mock(Part::class)->makePartial();
        $part2->id = 2;

        $upsertColorAction = Mockery::mock(UpsertColorAction::class);
        $upsertColorAction->shouldReceive('execute')->twice()->andReturn($color1, $color2);

        $upsertPartAction = Mockery::mock(UpsertPartAction::class);
        $upsertPartAction->shouldReceive('execute')->twice()->andReturn($part1, $part2);

        $setPartQueryBuilder = Mockery::mock(Builder::class);
        $setPartQueryBuilder->shouldReceive('where')->andReturnSelf();
        $setPartQueryBuilder->shouldReceive('first')->andReturn(null);

        $newSetPart1 = Mockery::mock(SetPart::class)->makePartial();
        $newSetPart1->shouldReceive('save')->once();

        $newSetPart2 = Mockery::mock(SetPart::class)->makePartial();
        $newSetPart2->shouldReceive('save')->once();

        $setPart = Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->andReturn($setPartQueryBuilder);
        $setPart->shouldReceive('newInstance')->twice()->andReturn($newSetPart1, $newSetPart2);

        $action = new StoreSetPartsAction($upsertColorAction, $upsertPartAction, $setPart);

        $partsData = [
            new LegoSetPartData(
                part: new LegoPartData(partNum: '3001', name: 'Brick 2 x 4', categoryId: 11, imageUrl: null),
                color: new LegoColorData(id: 1, name: 'White', rgb: 'FFFFFF', isTransparent: false),
                quantity: 5,
                isSpare: false,
                elementId: null,
            ),
            new LegoSetPartData(
                part: new LegoPartData(partNum: '3002', name: 'Brick 2 x 3', categoryId: null, imageUrl: null),
                color: new LegoColorData(id: 2, name: 'Black', rgb: '000000', isTransparent: false),
                quantity: 3,
                isSpare: true,
                elementId: null,
            ),
        ];

        // act
        $action->execute($set, $partsData);

        // assert
        expect($newSetPart1->quantity)->toBe(5);
        expect($newSetPart1->is_spare)->toBeFalse();
        expect($newSetPart2->quantity)->toBe(3);
        expect($newSetPart2->is_spare)->toBeTrue();
    });

    it('should handle empty parts data', function (): void {
        // arrange
        $set = Mockery::mock(Set::class)->makePartial();
        $set->id = 1;

        $upsertColorAction = Mockery::mock(UpsertColorAction::class);
        $upsertPartAction = Mockery::mock(UpsertPartAction::class);
        $setPart = Mockery::mock(SetPart::class);

        $action = new StoreSetPartsAction($upsertColorAction, $upsertPartAction, $setPart);

        // act - no exception should be thrown
        $action->execute($set, []);

        // assert - verify no calls were made
        $upsertColorAction->shouldNotHaveReceived('execute');
        $upsertPartAction->shouldNotHaveReceived('execute');
    });
});

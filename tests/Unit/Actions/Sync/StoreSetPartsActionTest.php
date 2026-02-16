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
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;

describe('StoreSetPartsAction', function (): void {
    beforeEach(function (): void {
        $this->db = Mockery::mock(ConnectionInterface::class);
        $this->db->allows('transaction')->andReturnUsing(fn (Closure $callback) => $callback());
    });

    it('should create set parts when they do not exist', function (): void {
        // arrange
        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(1);

        $color = Mockery::mock(Color::class);
        $color->allows('getAttribute')->with('id')->andReturn(1);

        $part = Mockery::mock(Part::class);
        $part->allows('getAttribute')->with('id')->andReturn(1);

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

        $newSetPartSavedValues = [];
        $newSetPart = Mockery::mock(SetPart::class);
        $newSetPart->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$newSetPartSavedValues): void {
            $newSetPartSavedValues[$key] = $value;
        });
        $newSetPart->allows('getAttribute')->andReturnUsing(function ($key) use (&$newSetPartSavedValues): mixed {
            return $newSetPartSavedValues[$key] ?? null;
        });
        $newSetPart->shouldReceive('save')->once();

        $setPart = Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->andReturn($setPartQueryBuilder);
        $setPart->shouldReceive('newInstance')->once()->andReturn($newSetPart);

        $action = new StoreSetPartsAction($upsertColorAction, $upsertPartAction, $setPart, $this->db);

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
        expect($newSetPartSavedValues['set_id'])->toBe(1);
        expect($newSetPartSavedValues['part_id'])->toBe(1);
        expect($newSetPartSavedValues['color_id'])->toBe(1);
        expect($newSetPartSavedValues['quantity'])->toBe(10);
        expect($newSetPartSavedValues['is_spare'])->toBeFalse();
        expect($newSetPartSavedValues['element_id'])->toBe('300101');
    });

    it('should update existing set parts', function (): void {
        // arrange
        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(1);

        $color = Mockery::mock(Color::class);
        $color->allows('getAttribute')->with('id')->andReturn(1);

        $part = Mockery::mock(Part::class);
        $part->allows('getAttribute')->with('id')->andReturn(1);

        $upsertColorAction = Mockery::mock(UpsertColorAction::class);
        $upsertColorAction->shouldReceive('execute')->once()->andReturn($color);

        $upsertPartAction = Mockery::mock(UpsertPartAction::class);
        $upsertPartAction->shouldReceive('execute')->once()->andReturn($part);

        $existingSavedValues = [
            'set_id' => 1,
            'part_id' => 1,
            'color_id' => 1,
            'is_spare' => false,
            'quantity' => 5,
        ];
        $existingSetPart = Mockery::mock(SetPart::class);
        $existingSetPart->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$existingSavedValues): void {
            $existingSavedValues[$key] = $value;
        });
        $existingSetPart->allows('getAttribute')->andReturnUsing(function ($key) use (&$existingSavedValues): mixed {
            return $existingSavedValues[$key] ?? null;
        });
        $existingSetPart->shouldReceive('save')->once();

        $setPartQueryBuilder = Mockery::mock(Builder::class);
        $setPartQueryBuilder->shouldReceive('where')->andReturnSelf();
        $setPartQueryBuilder->shouldReceive('first')->andReturn($existingSetPart);

        $setPart = Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->andReturn($setPartQueryBuilder);

        $action = new StoreSetPartsAction($upsertColorAction, $upsertPartAction, $setPart, $this->db);

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
        expect($existingSavedValues['quantity'])->toBe(15);
        expect($existingSavedValues['element_id'])->toBe('NEW123');
    });

    it('should process multiple parts', function (): void {
        // arrange
        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(1);

        $color1 = Mockery::mock(Color::class);
        $color1->allows('getAttribute')->with('id')->andReturn(1);

        $color2 = Mockery::mock(Color::class);
        $color2->allows('getAttribute')->with('id')->andReturn(2);

        $part1 = Mockery::mock(Part::class);
        $part1->allows('getAttribute')->with('id')->andReturn(1);

        $part2 = Mockery::mock(Part::class);
        $part2->allows('getAttribute')->with('id')->andReturn(2);

        $upsertColorAction = Mockery::mock(UpsertColorAction::class);
        $upsertColorAction->shouldReceive('execute')->twice()->andReturn($color1, $color2);

        $upsertPartAction = Mockery::mock(UpsertPartAction::class);
        $upsertPartAction->shouldReceive('execute')->twice()->andReturn($part1, $part2);

        $setPartQueryBuilder = Mockery::mock(Builder::class);
        $setPartQueryBuilder->shouldReceive('where')->andReturnSelf();
        $setPartQueryBuilder->shouldReceive('first')->andReturn(null);

        $newSetPart1SavedValues = [];
        $newSetPart1 = Mockery::mock(SetPart::class);
        $newSetPart1->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$newSetPart1SavedValues): void {
            $newSetPart1SavedValues[$key] = $value;
        });
        $newSetPart1->allows('getAttribute')->andReturnUsing(function ($key) use (&$newSetPart1SavedValues): mixed {
            return $newSetPart1SavedValues[$key] ?? null;
        });
        $newSetPart1->shouldReceive('save')->once();

        $newSetPart2SavedValues = [];
        $newSetPart2 = Mockery::mock(SetPart::class);
        $newSetPart2->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$newSetPart2SavedValues): void {
            $newSetPart2SavedValues[$key] = $value;
        });
        $newSetPart2->allows('getAttribute')->andReturnUsing(function ($key) use (&$newSetPart2SavedValues): mixed {
            return $newSetPart2SavedValues[$key] ?? null;
        });
        $newSetPart2->shouldReceive('save')->once();

        $setPart = Mockery::mock(SetPart::class);
        $setPart->shouldReceive('newQuery')->andReturn($setPartQueryBuilder);
        $setPart->shouldReceive('newInstance')->twice()->andReturn($newSetPart1, $newSetPart2);

        $action = new StoreSetPartsAction($upsertColorAction, $upsertPartAction, $setPart, $this->db);

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
        expect($newSetPart1SavedValues['quantity'])->toBe(5);
        expect($newSetPart1SavedValues['is_spare'])->toBeFalse();
        expect($newSetPart2SavedValues['quantity'])->toBe(3);
        expect($newSetPart2SavedValues['is_spare'])->toBeTrue();
    });

    it('should handle empty parts data', function (): void {
        // arrange
        $set = Mockery::mock(Set::class);
        $set->allows('getAttribute')->with('id')->andReturn(1);

        $upsertColorAction = Mockery::mock(UpsertColorAction::class);
        $upsertColorAction->shouldReceive('execute')->never();

        $upsertPartAction = Mockery::mock(UpsertPartAction::class);
        $upsertPartAction->shouldReceive('execute')->never();

        $setPart = Mockery::mock(SetPart::class);

        $action = new StoreSetPartsAction($upsertColorAction, $upsertPartAction, $setPart, $this->db);

        // act
        $action->execute($set, []);

        // assert - Mockery verifies expectations automatically
    });
});

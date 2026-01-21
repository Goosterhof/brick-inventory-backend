<?php

declare(strict_types=1);

use App\Actions\StorageOption\AssignPartToStorageAction;
use App\DataTransferObjects\AssignPartToStorageData;
use App\Models\Color;
use App\Models\Part;
use App\Models\StorageOption;
use App\Models\StorageOptionPart;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('AssignPartToStorageAction', function (): void {
    it('should assign a part to a storage option', function (): void {
        // arrange
        $storageOption = StorageOption::factory()->create();
        $part = Part::factory()->create();
        $action = new AssignPartToStorageAction;
        $data = new AssignPartToStorageData(
            storageOptionId: $storageOption->id,
            partId: $part->id,
            quantity: 50,
        );

        // act
        $result = $action->execute($data);

        // assert
        expect($result)->toBeInstanceOf(StorageOptionPart::class)
            ->and($result->storage_option_id)->toBe($storageOption->id)
            ->and($result->part_id)->toBe($part->id)
            ->and($result->quantity)->toBe(50);
    });

    it('should assign a part with color', function (): void {
        // arrange
        $storageOption = StorageOption::factory()->create();
        $part = Part::factory()->create();
        $color = Color::factory()->create();
        $action = new AssignPartToStorageAction;
        $data = new AssignPartToStorageData(
            storageOptionId: $storageOption->id,
            partId: $part->id,
            colorId: $color->id,
            quantity: 25,
        );

        // act
        $result = $action->execute($data);

        // assert
        expect($result->color_id)->toBe($color->id);
    });

    it('should update existing assignment', function (): void {
        // arrange
        $storageOption = StorageOption::factory()->create();
        $part = Part::factory()->create();
        StorageOptionPart::factory()->create([
            'storage_option_id' => $storageOption->id,
            'part_id' => $part->id,
            'color_id' => null,
            'quantity' => 10,
        ]);
        $action = new AssignPartToStorageAction;
        $data = new AssignPartToStorageData(
            storageOptionId: $storageOption->id,
            partId: $part->id,
            quantity: 100,
        );

        // act
        $result = $action->execute($data);

        // assert
        expect($result->quantity)->toBe(100)
            ->and(StorageOptionPart::where('storage_option_id', $storageOption->id)
                ->where('part_id', $part->id)
                ->count())->toBe(1);
    });
});

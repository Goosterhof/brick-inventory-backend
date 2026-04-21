<?php

declare(strict_types = 1);

use App\DataTransferObjects\Result\FamilySet\FamilySetCompletionData;
use App\Http\Resources\FamilySetCompletionResourceData;

covers(FamilySetCompletionResourceData::class);

describe('FamilySetCompletionResourceData', function(): void {
    it('should create resource from FamilySetCompletionData', function(): void {
        // arrange
        $data = new FamilySetCompletionData(
            familySetId: 1,
            setNum: '75192-1',
            totalParts: 7_541,
            storedParts: 3_200,
            percentage: 42.43,
        );

        // act
        $resource = FamilySetCompletionResourceData::from($data);

        // assert
        expect($resource)->toBeInstanceOf(FamilySetCompletionResourceData::class)
            ->and($resource->family_set_id)->toBe(1)
            ->and($resource->set_num)->toBe('75192-1')
            ->and($resource->total_parts)->toBe(7_541)
            ->and($resource->stored_parts)->toBe(3_200)
            ->and($resource->percentage)->toBe(42.43);
    });

    it('should serialize to array with snake_case keys', function(): void {
        // arrange
        $data = new FamilySetCompletionData(
            familySetId: 5,
            setNum: '10294-1',
            totalParts: 2_532,
            storedParts: 2_532,
            percentage: 100.0,
        );

        // act
        $array = FamilySetCompletionResourceData::from($data)->toArray();

        // assert
        expect($array)->toBe([
            'family_set_id' => 5,
            'set_num' => '10294-1',
            'total_parts' => 2_532,
            'stored_parts' => 2_532,
            'percentage' => 100.0,
        ]);
    });

    it('should handle nullable fields', function(): void {
        // arrange
        $data = new FamilySetCompletionData(
            familySetId: 3,
            setNum: '42151-1',
            totalParts: null,
            storedParts: null,
            percentage: null,
        );

        // act
        $resource = FamilySetCompletionResourceData::from($data);

        // assert
        expect($resource->total_parts)->toBeNull()
            ->and($resource->stored_parts)->toBeNull()
            ->and($resource->percentage)->toBeNull();
    });
});

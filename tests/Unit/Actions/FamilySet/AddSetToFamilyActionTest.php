<?php

declare(strict_types=1);

use App\Actions\FamilySet\AddSetToFamilyAction;
use App\Actions\FamilySet\UpdateFamilySetAction;
use App\DataTransferObjects\CreateFamilySetData;
use App\Enums\FamilySetStatus;
use App\Models\Family;
use App\Models\FamilySet;
use App\Models\Set;
use App\Services\RebrickableService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('AddSetToFamilyAction', function (): void {
    it('should create a family set with existing set', function (): void {
        // arrange
        $family = Family::factory()->create();
        $set = Set::factory()->create(['set_num' => '75192-1']);

        $rebrickableService = Mockery::mock(RebrickableService::class);
        $rebrickableService->shouldReceive('getSet')
            ->with('75192-1')
            ->andReturn($set);

        $updateAction = new UpdateFamilySetAction;
        $action = new AddSetToFamilyAction($rebrickableService, $updateAction);
        $data = new CreateFamilySetData(
            setNum: '75192-1',
            quantity: 2,
            status: FamilySetStatus::Built,
            purchaseDate: Carbon::parse('2024-01-15'),
            notes: 'Test notes',
        );

        // act
        $familySet = $action->execute($family, $data);

        // assert
        expect($familySet)->toBeInstanceOf(FamilySet::class)
            ->and($familySet->family_id)->toBe($family->id)
            ->and($familySet->set_id)->toBe($set->id)
            ->and($familySet->quantity)->toBe(2)
            ->and($familySet->status)->toBe(FamilySetStatus::Built)
            ->and($familySet->purchase_date->format('Y-m-d'))->toBe('2024-01-15')
            ->and($familySet->notes)->toBe('Test notes');
    });

    it('should use default values when not provided', function (): void {
        // arrange
        $family = Family::factory()->create();
        $set = Set::factory()->create(['set_num' => '75192-1']);

        $rebrickableService = Mockery::mock(RebrickableService::class);
        $rebrickableService->shouldReceive('getSet')
            ->with('75192-1')
            ->andReturn($set);

        $updateAction = new UpdateFamilySetAction;
        $action = new AddSetToFamilyAction($rebrickableService, $updateAction);
        $data = new CreateFamilySetData(setNum: '75192-1');

        // act
        $familySet = $action->execute($family, $data);

        // assert
        expect($familySet->quantity)->toBe(1)
            ->and($familySet->status)->toBe(FamilySetStatus::Sealed)
            ->and($familySet->purchase_date)->toBeNull()
            ->and($familySet->notes)->toBeNull();
    });

    it('should load the set relationship', function (): void {
        // arrange
        $family = Family::factory()->create();
        $set = Set::factory()->create(['set_num' => '75192-1', 'name' => 'Millennium Falcon']);

        $rebrickableService = Mockery::mock(RebrickableService::class);
        $rebrickableService->shouldReceive('getSet')
            ->with('75192-1')
            ->andReturn($set);

        $updateAction = new UpdateFamilySetAction;
        $action = new AddSetToFamilyAction($rebrickableService, $updateAction);
        $data = new CreateFamilySetData(setNum: '75192-1');

        // act
        $familySet = $action->execute($family, $data);

        // assert
        expect($familySet->relationLoaded('set'))->toBeTrue()
            ->and($familySet->set->name)->toBe('Millennium Falcon');
    });

    it('should persist the family set to database', function (): void {
        // arrange
        $family = Family::factory()->create();
        $set = Set::factory()->create(['set_num' => '75192-1']);

        $rebrickableService = Mockery::mock(RebrickableService::class);
        $rebrickableService->shouldReceive('getSet')
            ->with('75192-1')
            ->andReturn($set);

        $updateAction = new UpdateFamilySetAction;
        $action = new AddSetToFamilyAction($rebrickableService, $updateAction);
        $data = new CreateFamilySetData(setNum: '75192-1', quantity: 3);

        // act
        $action->execute($family, $data);

        // assert
        expect(FamilySet::where('family_id', $family->id)->where('set_id', $set->id)->exists())->toBeTrue();
    });
});

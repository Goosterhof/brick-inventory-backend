<?php

declare(strict_types=1);

use App\Actions\FamilySet\ImportOwnedSetsAction;
use App\Data\ImportOwnedSetsResultData;
use App\Enums\ImportJobStatus;
use App\Jobs\ImportOwnedSetsJob;
use App\Models\Family;
use App\Models\ImportJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ImportOwnedSetsJob::class);

uses(RefreshDatabase::class);

describe('ImportOwnedSetsJob', function (): void {
    it('should update import job to completed on successful import', function (): void {
        // arrange
        $family = Family::factory()->create(['rebrickable_user_token' => 'test-token']);

        /** @var ImportJob $importJob */
        $importJob = ImportJob::factory()->forFamily($family)->create();

        $result = new ImportOwnedSetsResultData(
            created: 5,
            updated: 3,
            skipped: 0,
            total: 8,
            complete: true,
        );

        $importOwnedSetsAction = Mockery::mock(ImportOwnedSetsAction::class);
        $importOwnedSetsAction->shouldReceive('execute')
            ->once()
            ->andReturn($result);

        $job = new ImportOwnedSetsJob(importJobId: $importJob->id, familyId: $family->id);

        // act
        $job->handle($importOwnedSetsAction);

        // assert
        $importJob->refresh();
        expect($importJob->status)->toBe(ImportJobStatus::Completed);
        expect($importJob->total_sets)->toBe(8);
        expect($importJob->processed_sets)->toBe(8);
        expect($importJob->failed_sets)->toBe(0);
        expect($importJob->started_at)->not->toBeNull();
        expect($importJob->completed_at)->not->toBeNull();
    });

    it('should record skipped sets as failed set details', function (): void {
        // arrange
        $family = Family::factory()->create(['rebrickable_user_token' => 'test-token']);

        /** @var ImportJob $importJob */
        $importJob = ImportJob::factory()->forFamily($family)->create();

        $result = new ImportOwnedSetsResultData(
            created: 2,
            updated: 1,
            skipped: 2,
            total: 3,
            complete: true,
            skippedSetNums: ['75192-1', '10281-1'],
        );

        $importOwnedSetsAction = Mockery::mock(ImportOwnedSetsAction::class);
        $importOwnedSetsAction->shouldReceive('execute')
            ->once()
            ->andReturn($result);

        $job = new ImportOwnedSetsJob(importJobId: $importJob->id, familyId: $family->id);

        // act
        $job->handle($importOwnedSetsAction);

        // assert
        $importJob->refresh();
        expect($importJob->status)->toBe(ImportJobStatus::Completed);
        expect($importJob->failed_sets)->toBe(2);
        assert($importJob->failed_set_details !== null);
        expect($importJob->failed_set_details)->toHaveCount(2);
        expect($importJob->failed_set_details[0]['set_num'])->toBe('75192-1');
        expect($importJob->failed_set_details[1]['set_num'])->toBe('10281-1');
    });

    it('should mark import as failed when result is incomplete', function (): void {
        // arrange
        $family = Family::factory()->create(['rebrickable_user_token' => 'test-token']);

        /** @var ImportJob $importJob */
        $importJob = ImportJob::factory()->forFamily($family)->create();

        $result = new ImportOwnedSetsResultData(
            created: 3,
            updated: 0,
            skipped: 0,
            total: 3,
            complete: false,
            error: 'Import incomplete: API error. 3 sets were imported successfully. Retry to fetch remaining sets.',
        );

        $importOwnedSetsAction = Mockery::mock(ImportOwnedSetsAction::class);
        $importOwnedSetsAction->shouldReceive('execute')
            ->once()
            ->andReturn($result);

        $job = new ImportOwnedSetsJob(importJobId: $importJob->id, familyId: $family->id);

        // act
        $job->handle($importOwnedSetsAction);

        // assert
        $importJob->refresh();
        expect($importJob->status)->toBe(ImportJobStatus::Failed);
        assert($importJob->failed_set_details !== null);
        expect($importJob->failed_set_details)->toHaveCount(1);
        expect($importJob->failed_set_details[0]['error'])->toContain('Import incomplete');
    });

    it('should mark import as failed when job fails with exception', function (): void {
        // arrange
        $family = Family::factory()->create();

        /** @var ImportJob $importJob */
        $importJob = ImportJob::factory()->forFamily($family)->create();

        $job = new ImportOwnedSetsJob(importJobId: $importJob->id, familyId: $family->id);

        // act
        $job->failed(new RuntimeException('Connection timeout'));

        // assert
        $importJob->refresh();
        expect($importJob->status)->toBe(ImportJobStatus::Failed);
        expect($importJob->completed_at)->not->toBeNull();
        assert($importJob->failed_set_details !== null);
        expect($importJob->failed_set_details)->toHaveCount(1);
        expect($importJob->failed_set_details[0]['error'])->toBe('Connection timeout');
    });

    it('should handle failed() gracefully when import job does not exist', function (): void {
        // arrange
        $job = new ImportOwnedSetsJob(importJobId: 999999, familyId: 1);

        // act - should not throw
        $job->failed(new RuntimeException('Some error'));

        // assert - no import job was created or modified
        expect(ImportJob::query()->where('id', 999999)->exists())->toBeFalse();
    });

    it('should set status to in_progress before executing import', function (): void {
        // arrange
        $family = Family::factory()->create(['rebrickable_user_token' => 'test-token']);

        /** @var ImportJob $importJob */
        $importJob = ImportJob::factory()->forFamily($family)->create();

        $statusDuringExecution = null;
        $importOwnedSetsAction = Mockery::mock(ImportOwnedSetsAction::class);
        $importOwnedSetsAction->shouldReceive('execute')
            ->once()
            ->andReturnUsing(function () use ($importJob, &$statusDuringExecution): ImportOwnedSetsResultData {
                $importJob->refresh();
                $statusDuringExecution = $importJob->status;

                return new ImportOwnedSetsResultData(
                    created: 0,
                    updated: 0,
                    skipped: 0,
                    total: 0,
                    complete: true,
                );
            });

        $job = new ImportOwnedSetsJob(importJobId: $importJob->id, familyId: $family->id);

        // act
        $job->handle($importOwnedSetsAction);

        // assert
        expect($statusDuringExecution)->toBe(ImportJobStatus::InProgress);
    });
});

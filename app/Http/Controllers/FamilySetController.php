<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FamilySet\CreateFamilySetAction;
use App\Actions\FamilySet\DeleteFamilySetAction;
use App\Actions\FamilySet\GetFamilySetsAction;
use App\Actions\FamilySet\ImportOwnedSetsAction;
use App\Actions\FamilySet\UpdateFamilySetAction;
use App\Http\Requests\FamilySet\StoreFamilySetRequest;
use App\Http\Requests\FamilySet\UpdateFamilySetRequest;
use App\Http\Resources\FamilySetResourceData;
use App\Models\FamilySet;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class FamilySetController extends Controller
{
    public function __construct(
        private readonly GetFamilySetsAction $getFamilySetsAction,
        private readonly CreateFamilySetAction $createFamilySetAction,
        private readonly UpdateFamilySetAction $updateFamilySetAction,
        private readonly DeleteFamilySetAction $deleteFamilySetAction,
        private readonly ImportOwnedSetsAction $importOwnedSetsAction,
    ) {}

    /**
     * @return array<int, FamilySetResourceData>
     */
    public function index(#[CurrentUser] User $user): array
    {
        $familySets = $this->getFamilySetsAction->execute($user);

        return FamilySetResourceData::collection($familySets);
    }

    public function store(StoreFamilySetRequest $storeFamilySetRequest, #[CurrentUser] User $user): JsonResponse
    {
        $familySet = $this->createFamilySetAction->execute($user->family, $storeFamilySetRequest);

        return FamilySetResourceData::from($familySet)->toResponseWithStatus(201);
    }

    public function show(FamilySet $familySet): JsonResponse
    {
        return FamilySetResourceData::from($familySet)->toResponse();
    }

    public function update(UpdateFamilySetRequest $updateFamilySetRequest, FamilySet $familySet): JsonResponse
    {
        $familySet = $this->updateFamilySetAction->execute($familySet, $updateFamilySetRequest);

        return FamilySetResourceData::from($familySet)->toResponse();
    }

    public function destroy(FamilySet $familySet): JsonResponse
    {
        $this->deleteFamilySetAction->execute($familySet);

        return response()->json(null, 204);
    }

    public function importFromRebrickable(#[CurrentUser] User $user): JsonResponse
    {
        $importOwnedSetsResultData = $this->importOwnedSetsAction->execute($user->family);

        $response = [
            'message' => 'Import completed successfully',
            'created' => $importOwnedSetsResultData->created,
            'updated' => $importOwnedSetsResultData->updated,
            'skipped' => $importOwnedSetsResultData->skipped,
            'total' => $importOwnedSetsResultData->total,
        ];

        if ($importOwnedSetsResultData->skippedSetNums !== []) {
            $response['skipped_set_nums'] = $importOwnedSetsResultData->skippedSetNums;
        }

        return response()->json($response);
    }
}

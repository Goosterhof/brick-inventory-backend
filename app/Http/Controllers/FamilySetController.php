<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FamilySet\CreateFamilySetAction;
use App\Actions\FamilySet\DeleteFamilySetAction;
use App\Actions\FamilySet\GetFamilySetCompletionAction;
use App\Actions\FamilySet\GetFamilySetsAction;
use App\Actions\FamilySet\ImportOwnedSetsAction;
use App\Actions\FamilySet\UpdateFamilySetAction;
use App\Http\Requests\FamilySet\StoreFamilySetRequest;
use App\Http\Requests\FamilySet\UpdateFamilySetRequest;
use App\Http\Resources\FamilySetCompletionResourceData;
use App\Http\Resources\FamilySetResourceData;
use App\Models\FamilySet;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilySetController extends Controller
{
    public function index(
        #[CurrentUser] User $user,
        GetFamilySetsAction $getFamilySetsAction,
        Request $request,
    ): JsonResponse {
        $cursorPaginator = $getFamilySetsAction->execute(
            user: $user,
            perPage: $request->integer('per_page', 25),
            cursor: $request->query('cursor'),
        );

        return new JsonResponse(
            $cursorPaginator->through(fn (FamilySet $familySet): array => FamilySetResourceData::from($familySet)->toArray()),
        );
    }

    /**
     * @return array<int, FamilySetCompletionResourceData>
     */
    public function completion(
        #[CurrentUser] User $user,
        GetFamilySetCompletionAction $getFamilySetCompletionAction,
    ): array {
        $completionData = $getFamilySetCompletionAction->execute($user->family);

        return array_map(
            FamilySetCompletionResourceData::from(...),
            $completionData,
        );
    }

    public function store(
        StoreFamilySetRequest $storeFamilySetRequest,
        #[CurrentUser] User $user,
        CreateFamilySetAction $createFamilySetAction,
    ): JsonResponse {
        $familySet = $createFamilySetAction->execute($user->family, $storeFamilySetRequest->toDto());

        return FamilySetResourceData::from($familySet)->toResponseWithStatus(201);
    }

    public function show(FamilySet $familySet): JsonResponse
    {
        return FamilySetResourceData::from($familySet)->toResponse();
    }

    public function update(
        UpdateFamilySetRequest $updateFamilySetRequest,
        FamilySet $familySet,
        UpdateFamilySetAction $updateFamilySetAction,
    ): JsonResponse {
        $familySet = $updateFamilySetAction->execute($familySet, $updateFamilySetRequest->toDto());

        return FamilySetResourceData::from($familySet)->toResponse();
    }

    public function destroy(FamilySet $familySet, DeleteFamilySetAction $deleteFamilySetAction): JsonResponse
    {
        $deleteFamilySetAction->execute($familySet);

        return response()->json(null, 204);
    }

    public function importFromRebrickable(
        #[CurrentUser] User $user,
        ImportOwnedSetsAction $importOwnedSetsAction,
    ): JsonResponse {
        $importOwnedSetsResultData = $importOwnedSetsAction->execute($user->family);

        $message = $importOwnedSetsResultData->complete
            ? 'Import completed successfully'
            : sprintf('Import partially completed: %d sets imported', $importOwnedSetsResultData->created + $importOwnedSetsResultData->updated);

        $response = [
            'message' => $message,
            'created' => $importOwnedSetsResultData->created,
            'updated' => $importOwnedSetsResultData->updated,
            'skipped' => $importOwnedSetsResultData->skipped,
            'total' => $importOwnedSetsResultData->total,
            'complete' => $importOwnedSetsResultData->complete,
        ];

        if ($importOwnedSetsResultData->error !== null) {
            $response['error'] = $importOwnedSetsResultData->error;
        }

        if ($importOwnedSetsResultData->skippedSetNums !== []) {
            $response['skipped_set_nums'] = $importOwnedSetsResultData->skippedSetNums;
        }

        return response()->json($response);
    }
}

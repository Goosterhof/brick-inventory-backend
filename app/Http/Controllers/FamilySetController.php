<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FamilySet\CreateFamilySetAction;
use App\Actions\FamilySet\DeleteFamilySetAction;
use App\Actions\FamilySet\UpdateFamilySetAction;
use App\Http\Requests\StoreFamilySetRequest;
use App\Http\Requests\UpdateFamilySetRequest;
use App\Http\Resources\FamilySetResourceData;
use App\Models\FamilySet;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;

class FamilySetController extends Controller
{
    public function __construct(
        private readonly CreateFamilySetAction $createFamilySetAction,
        private readonly UpdateFamilySetAction $updateFamilySetAction,
        private readonly DeleteFamilySetAction $deleteFamilySetAction,
    ) {}

    /**
     * @return array<int, FamilySetResourceData>
     */
    public function index(#[CurrentUser] User $user): array
    {
        $familySets = FamilySet::where('family_id', $user->family_id)
            ->with('set')
            ->orderBy('created_at', 'desc')
            ->get();

        return FamilySetResourceData::collection($familySets);
    }

    public function store(StoreFamilySetRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        try {
            $family = $user->family;

            if ($family === null) {
                return response()->json(['error' => 'User does not belong to a family'], 400);
            }

            $familySet = $this->createFamilySetAction->execute($family, $request);

            return FamilySetResourceData::from($familySet)->toResponseWithStatus(201);
        } catch (RequestException $requestException) {
            $status = $requestException->response->status();
            $message = match ($status) {
                404 => 'Set not found',
                401 => 'Invalid API key',
                default => 'Failed to fetch set data',
            };

            return response()->json(['error' => $message], $status);
        }
    }

    public function show(#[CurrentUser] User $user, FamilySet $familySet): FamilySetResourceData|JsonResponse
    {
        if ($familySet->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $familySet->load('set');

        return FamilySetResourceData::from($familySet);
    }

    public function update(
        UpdateFamilySetRequest $request,
        FamilySet $familySet,
        #[CurrentUser] User $user,
    ): FamilySetResourceData|JsonResponse {
        if ($familySet->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $familySet = $this->updateFamilySetAction->execute($familySet, $request);
        $familySet->load('set');

        return FamilySetResourceData::from($familySet);
    }

    public function destroy(#[CurrentUser] User $user, FamilySet $familySet): JsonResponse
    {
        if ($familySet->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $this->deleteFamilySetAction->execute($familySet);

        return response()->json(null, 204);
    }
}

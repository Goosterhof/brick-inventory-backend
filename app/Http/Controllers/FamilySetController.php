<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FamilySet\AddSetToFamilyAction;
use App\Actions\FamilySet\RemoveFamilySetAction;
use App\Actions\FamilySet\UpdateFamilySetAction;
use App\Http\Requests\StoreFamilySetRequest;
use App\Http\Requests\UpdateFamilySetRequest;
use App\Http\Resources\FamilySetResource;
use App\Models\FamilySet;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FamilySetController extends Controller
{
    public function __construct(
        private readonly AddSetToFamilyAction $addSetToFamilyAction,
        private readonly UpdateFamilySetAction $updateFamilySetAction,
        private readonly RemoveFamilySetAction $removeFamilySetAction,
    ) {}

    public function index(#[CurrentUser] User $user): AnonymousResourceCollection
    {
        $familySets = FamilySet::where('family_id', $user->family_id)
            ->with('set')
            ->orderBy('created_at', 'desc')
            ->get();

        return FamilySetResource::collection($familySets);
    }

    public function store(StoreFamilySetRequest $request, #[CurrentUser] User $user): FamilySetResource|JsonResponse
    {
        try {
            $family = $user->family;

            if ($family === null) {
                return response()->json(['error' => 'User does not belong to a family'], 400);
            }

            $familySet = $this->addSetToFamilyAction->execute($family, $request);

            return new FamilySetResource($familySet);
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

    public function show(#[CurrentUser] User $user, FamilySet $familySet): FamilySetResource|JsonResponse
    {
        if ($familySet->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $familySet->load('set');

        return new FamilySetResource($familySet);
    }

    public function update(
        UpdateFamilySetRequest $request,
        FamilySet $familySet,
        #[CurrentUser] User $user,
    ): FamilySetResource|JsonResponse {
        if ($familySet->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $familySet = $this->updateFamilySetAction->execute($familySet, $request);
        $familySet->load('set');

        return new FamilySetResource($familySet);
    }

    public function destroy(#[CurrentUser] User $user, FamilySet $familySet): JsonResponse
    {
        if ($familySet->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $this->removeFamilySetAction->execute($familySet);

        return response()->json(null, 204);
    }
}

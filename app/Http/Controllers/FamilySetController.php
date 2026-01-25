<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FamilySet\CreateFamilySetAction;
use App\Actions\FamilySet\DeleteFamilySetAction;
use App\Actions\FamilySet\GetFamilySetAction;
use App\Actions\FamilySet\GetFamilySetsAction;
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
        private readonly GetFamilySetsAction $getFamilySetsAction,
        private readonly GetFamilySetAction $getFamilySetAction,
        private readonly CreateFamilySetAction $createFamilySetAction,
        private readonly UpdateFamilySetAction $updateFamilySetAction,
        private readonly DeleteFamilySetAction $deleteFamilySetAction,
    ) {}

    /**
     * @return array<int, FamilySetResourceData>
     */
    public function index(#[CurrentUser] User $user): array
    {
        $familySets = $this->getFamilySetsAction->execute($user);

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

    public function show(FamilySet $familySet): FamilySetResourceData
    {
        $familySet = $this->getFamilySetAction->execute($familySet);

        return FamilySetResourceData::from($familySet);
    }

    public function update(UpdateFamilySetRequest $request, FamilySet $familySet): FamilySetResourceData
    {
        $familySet = $this->updateFamilySetAction->execute($familySet, $request);
        $familySet = $this->getFamilySetAction->execute($familySet);

        return FamilySetResourceData::from($familySet);
    }

    public function destroy(FamilySet $familySet): JsonResponse
    {
        $this->deleteFamilySetAction->execute($familySet);

        return response()->json(null, 204);
    }
}

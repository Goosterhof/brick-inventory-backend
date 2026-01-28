<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FamilySet\CreateFamilySetAction;
use App\Actions\FamilySet\DeleteFamilySetAction;
use App\Actions\FamilySet\GetFamilySetsAction;
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

    public function show(FamilySet $familySet): FamilySetResourceData
    {
        return FamilySetResourceData::from($familySet);
    }

    public function update(UpdateFamilySetRequest $updateFamilySetRequest, FamilySet $familySet): FamilySetResourceData
    {
        $familySet = $this->updateFamilySetAction->execute($familySet, $updateFamilySetRequest);

        return FamilySetResourceData::from($familySet);
    }

    public function destroy(FamilySet $familySet): JsonResponse
    {
        $this->deleteFamilySetAction->execute($familySet);

        return response()->json(null, 204);
    }
}

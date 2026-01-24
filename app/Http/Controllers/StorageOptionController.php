<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StorageOption\CreateStorageOptionAction;
use App\Actions\StorageOption\CreateStorageOptionPartAction;
use App\Actions\StorageOption\DeleteStorageOptionAction;
use App\Actions\StorageOption\DeleteStorageOptionPartAction;
use App\Actions\StorageOption\UpdateStorageOptionAction;
use App\Http\Requests\StorageOption\AssignPartRequest;
use App\Http\Requests\StorageOption\StoreStorageOptionRequest;
use App\Http\Requests\StorageOption\UpdateStorageOptionRequest;
use App\Http\Resources\StorageOptionPartResourceData;
use App\Http\Resources\StorageOptionResourceData;
use App\Models\StorageOption;
use App\Models\StorageOptionPart;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class StorageOptionController extends Controller
{
    public function __construct(
        private readonly CreateStorageOptionAction $createStorageOptionAction,
        private readonly UpdateStorageOptionAction $updateStorageOptionAction,
        private readonly DeleteStorageOptionAction $deleteStorageOptionAction,
        private readonly CreateStorageOptionPartAction $createStorageOptionPartAction,
        private readonly DeleteStorageOptionPartAction $deleteStorageOptionPartAction,
    ) {}

    /**
     * @return array<int, StorageOptionResourceData>
     */
    public function index(#[CurrentUser] User $user): array
    {
        $storageOptions = StorageOption::where('family_id', $user->family_id)
            ->whereNull('parent_id')
            ->with('children')
            ->get();

        return StorageOptionResourceData::collection($storageOptions);
    }

    public function store(StoreStorageOptionRequest $request): JsonResponse
    {
        $storageOption = $this->createStorageOptionAction->execute($request);
        $storageOption->load('children');

        return StorageOptionResourceData::from($storageOption)->toResponseWithStatus(201);
    }

    public function show(#[CurrentUser] User $user, StorageOption $storageOption): StorageOptionResourceData|JsonResponse
    {
        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $storageOption->load('children');

        return StorageOptionResourceData::from($storageOption);
    }

    public function update(
        UpdateStorageOptionRequest $request,
        StorageOption $storageOption,
        #[CurrentUser] User $user,
    ): StorageOptionResourceData|JsonResponse {
        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $storageOption = $this->updateStorageOptionAction->execute($storageOption, $request);
        $storageOption->load('children');

        return StorageOptionResourceData::from($storageOption);
    }

    public function destroy(#[CurrentUser] User $user, StorageOption $storageOption): JsonResponse
    {
        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $this->deleteStorageOptionAction->execute($storageOption);

        return response()->json(null, 204);
    }

    /**
     * @return array<int, StorageOptionPartResourceData>|JsonResponse
     */
    public function parts(#[CurrentUser] User $user, StorageOption $storageOption): array|JsonResponse
    {
        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $parts = $storageOption->storageOptionParts()->with(['part', 'color'])->get();

        return StorageOptionPartResourceData::collection($parts);
    }

    public function assignPart(
        AssignPartRequest $request,
        StorageOption $storageOption,
        #[CurrentUser] User $user,
    ): JsonResponse {
        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $storageOptionPart = $this->createStorageOptionPartAction->execute($storageOption, $request);
        $storageOptionPart->load(['part', 'color']);

        $resource = StorageOptionPartResourceData::from($storageOptionPart);
        $statusCode = $storageOptionPart->wasRecentlyCreated ? 201 : 200;

        return $resource->toResponseWithStatus($statusCode);
    }

    public function removePart(
        #[CurrentUser] User $user,
        StorageOption $storageOption,
        StorageOptionPart $part,
    ): JsonResponse {
        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($part->storage_option_id !== $storageOption->id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $this->deleteStorageOptionPartAction->execute($part);

        return response()->json(null, 204);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StorageOption\AssignPartToStorageAction;
use App\Actions\StorageOption\CreateStorageOptionAction;
use App\Actions\StorageOption\DeleteStorageOptionAction;
use App\Actions\StorageOption\DeleteStorageOptionPartAction;
use App\Actions\StorageOption\GetStorageOptionAction;
use App\Actions\StorageOption\GetStorageOptionPartsAction;
use App\Actions\StorageOption\GetStorageOptionsAction;
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
        private readonly GetStorageOptionsAction $getStorageOptionsAction,
        private readonly GetStorageOptionAction $getStorageOptionAction,
        private readonly GetStorageOptionPartsAction $getStorageOptionPartsAction,
        private readonly CreateStorageOptionAction $createStorageOptionAction,
        private readonly UpdateStorageOptionAction $updateStorageOptionAction,
        private readonly DeleteStorageOptionAction $deleteStorageOptionAction,
        private readonly AssignPartToStorageAction $assignPartToStorageAction,
        private readonly DeleteStorageOptionPartAction $deleteStorageOptionPartAction,
    ) {}

    /**
     * @return array<int, StorageOptionResourceData>
     */
    public function index(#[CurrentUser] User $user): array
    {
        $storageOptions = $this->getStorageOptionsAction->execute($user);

        return StorageOptionResourceData::collection($storageOptions);
    }

    public function store(StoreStorageOptionRequest $storeStorageOptionRequest): JsonResponse
    {
        $storageOption = $this->createStorageOptionAction->execute($storeStorageOptionRequest);
        $storageOption = $this->getStorageOptionAction->execute($storageOption);

        return StorageOptionResourceData::from($storageOption)->toResponseWithStatus(201);
    }

    public function show(StorageOption $storageOption): StorageOptionResourceData
    {
        $storageOption = $this->getStorageOptionAction->execute($storageOption);

        return StorageOptionResourceData::from($storageOption);
    }

    public function update(UpdateStorageOptionRequest $updateStorageOptionRequest, StorageOption $storageOption): StorageOptionResourceData
    {
        $storageOption = $this->updateStorageOptionAction->execute($storageOption, $updateStorageOptionRequest);
        $storageOption = $this->getStorageOptionAction->execute($storageOption);

        return StorageOptionResourceData::from($storageOption);
    }

    public function destroy(StorageOption $storageOption): JsonResponse
    {
        $this->deleteStorageOptionAction->execute($storageOption);

        return response()->json(null, 204);
    }

    /**
     * @return array<int, StorageOptionPartResourceData>
     */
    public function parts(StorageOption $storageOption): array
    {
        $parts = $this->getStorageOptionPartsAction->execute($storageOption);

        return StorageOptionPartResourceData::collection($parts);
    }

    public function assignPart(AssignPartRequest $assignPartRequest, StorageOption $storageOption): JsonResponse
    {
        $storageOptionPart = $this->assignPartToStorageAction->execute($storageOption, $assignPartRequest);
        $storageOptionPart->load(['part', 'color']);

        $storageOptionPartResourceData = StorageOptionPartResourceData::from($storageOptionPart);
        $statusCode = $storageOptionPart->wasRecentlyCreated ? 201 : 200;

        return $storageOptionPartResourceData->toResponseWithStatus($statusCode);
    }

    public function removePart(StorageOption $storageOption, StorageOptionPart $storageOptionPart): JsonResponse
    {
        if ($storageOptionPart->storage_option_id !== $storageOption->id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $this->deleteStorageOptionPartAction->execute($storageOptionPart);

        return response()->json(null, 204);
    }
}

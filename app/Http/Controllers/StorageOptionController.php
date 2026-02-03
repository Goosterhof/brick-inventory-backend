<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StorageOption\AssignPartToStorageAction;
use App\Actions\StorageOption\CreateStorageOptionAction;
use App\Actions\StorageOption\DeleteStorageOptionAction;
use App\Actions\StorageOption\DeleteStorageOptionPartAction;
use App\Actions\StorageOption\GetStorageOptionPartsAction;
use App\Actions\StorageOption\GetStorageOptionsAction;
use App\Actions\StorageOption\UpdateStorageOptionAction;
use App\Http\Requests\StorageOption\AssignPartRequest;
use App\Http\Requests\StorageOption\StorageOptionRequest;
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

    public function store(StorageOptionRequest $storageOptionRequest): JsonResponse
    {
        $storageOption = $this->createStorageOptionAction->execute($storageOptionRequest);

        return StorageOptionResourceData::from($storageOption)->toResponseWithStatus(201);
    }

    public function show(StorageOption $storageOption): JsonResponse
    {
        return StorageOptionResourceData::from($storageOption)->toResponse();
    }

    public function update(StorageOptionRequest $storageOptionRequest, StorageOption $storageOption): JsonResponse
    {
        $storageOption = $this->updateStorageOptionAction->execute($storageOption, $storageOptionRequest);

        return StorageOptionResourceData::from($storageOption)->toResponse();
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
        $statusCode = $storageOptionPart->wasRecentlyCreated ? 201 : 200;

        return StorageOptionPartResourceData::from($storageOptionPart)->toResponseWithStatus($statusCode);
    }

    public function removePart(StorageOption $storageOption, StorageOptionPart $storageOptionPart): JsonResponse
    {
        $this->deleteStorageOptionPartAction->execute($storageOptionPart);

        return response()->json(null, 204);
    }
}

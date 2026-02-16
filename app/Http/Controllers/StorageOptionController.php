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
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\JsonResponse;

class StorageOptionController extends Controller
{
    public function __construct(
        private readonly Gate $gate,
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
        $this->gate->authorize('viewAny', StorageOption::class);

        $storageOptions = $this->getStorageOptionsAction->execute($user);

        return StorageOptionResourceData::collection($storageOptions);
    }

    public function store(StorageOptionRequest $storageOptionRequest): JsonResponse
    {
        $this->gate->authorize('create', StorageOption::class);

        $storageOption = $this->createStorageOptionAction->execute($storageOptionRequest->toDto());

        return StorageOptionResourceData::from($storageOption)->toResponseWithStatus(201);
    }

    public function show(StorageOption $storageOption): JsonResponse
    {
        $this->gate->authorize('view', $storageOption);

        return StorageOptionResourceData::from($storageOption)->toResponse();
    }

    public function update(StorageOptionRequest $storageOptionRequest, StorageOption $storageOption): JsonResponse
    {
        $this->gate->authorize('update', $storageOption);

        $storageOption = $this->updateStorageOptionAction->execute($storageOption, $storageOptionRequest->toDto());

        return StorageOptionResourceData::from($storageOption)->toResponse();
    }

    public function destroy(StorageOption $storageOption): JsonResponse
    {
        $this->gate->authorize('delete', $storageOption);

        $this->deleteStorageOptionAction->execute($storageOption);

        return response()->json(null, 204);
    }

    /**
     * @return array<int, StorageOptionPartResourceData>
     */
    public function parts(StorageOption $storageOption): array
    {
        $this->gate->authorize('viewParts', $storageOption);

        $parts = $this->getStorageOptionPartsAction->execute($storageOption);

        return StorageOptionPartResourceData::collection($parts);
    }

    public function assignPart(AssignPartRequest $assignPartRequest, StorageOption $storageOption): JsonResponse
    {
        $this->gate->authorize('assignPart', $storageOption);

        $storageOptionPart = $this->assignPartToStorageAction->execute($storageOption, $assignPartRequest->toDto());
        $statusCode = $storageOptionPart->wasRecentlyCreated ? 201 : 200;

        return StorageOptionPartResourceData::from($storageOptionPart)->toResponseWithStatus($statusCode);
    }

    public function removePart(StorageOption $storageOption, StorageOptionPart $storageOptionPart): JsonResponse
    {
        $this->gate->authorize('delete', $storageOptionPart);

        $this->deleteStorageOptionPartAction->execute($storageOptionPart);

        return response()->json(null, 204);
    }
}

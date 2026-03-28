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
use Illuminate\Http\Request;

class StorageOptionController extends Controller
{
    public function index(
        #[CurrentUser] User $user,
        GetStorageOptionsAction $getStorageOptionsAction,
        Request $request,
    ): JsonResponse {
        $cursorPaginator = $getStorageOptionsAction->execute(
            user: $user,
            perPage: $request->integer('per_page', 25),
            cursor: $request->query('cursor'),
        );

        return new JsonResponse(
            $cursorPaginator->through(fn (StorageOption $storageOption): array => StorageOptionResourceData::from($storageOption)->toArray()),
        );
    }

    public function store(
        StorageOptionRequest $storageOptionRequest,
        CreateStorageOptionAction $createStorageOptionAction,
    ): JsonResponse {
        $storageOption = $createStorageOptionAction->execute($storageOptionRequest->toDto());

        return StorageOptionResourceData::from($storageOption)->toResponseWithStatus(201);
    }

    public function show(StorageOption $storageOption): JsonResponse
    {
        return StorageOptionResourceData::from($storageOption)->toResponse();
    }

    public function update(
        StorageOptionRequest $storageOptionRequest,
        StorageOption $storageOption,
        UpdateStorageOptionAction $updateStorageOptionAction,
    ): JsonResponse {
        $storageOption = $updateStorageOptionAction->execute($storageOption, $storageOptionRequest->toDto());

        return StorageOptionResourceData::from($storageOption)->toResponse();
    }

    public function destroy(
        StorageOption $storageOption,
        DeleteStorageOptionAction $deleteStorageOptionAction,
    ): JsonResponse {
        $deleteStorageOptionAction->execute($storageOption);

        return response()->json(null, 204);
    }

    public function parts(
        StorageOption $storageOption,
        GetStorageOptionPartsAction $getStorageOptionPartsAction,
        Request $request,
    ): JsonResponse {
        $cursorPaginator = $getStorageOptionPartsAction->execute(
            storageOption: $storageOption,
            perPage: $request->integer('per_page', 25),
            cursor: $request->query('cursor'),
        );

        return new JsonResponse(
            $cursorPaginator->through(fn (StorageOptionPart $storageOptionPart): array => StorageOptionPartResourceData::from($storageOptionPart)->toArray()),
        );
    }

    public function assignPart(
        AssignPartRequest $assignPartRequest,
        StorageOption $storageOption,
        AssignPartToStorageAction $assignPartToStorageAction,
    ): JsonResponse {
        $storageOptionPart = $assignPartToStorageAction->execute($storageOption, $assignPartRequest->toDto());
        $statusCode = $storageOptionPart->wasRecentlyCreated ? 201 : 200;

        return StorageOptionPartResourceData::from($storageOptionPart)->toResponseWithStatus($statusCode);
    }

    public function removePart(
        StorageOption $storageOption,
        StorageOptionPart $storageOptionPart,
        DeleteStorageOptionPartAction $deleteStorageOptionPartAction,
    ): JsonResponse {
        $deleteStorageOptionPartAction->execute($storageOptionPart);

        return response()->json(null, 204);
    }
}

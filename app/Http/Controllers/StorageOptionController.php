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
use App\Http\Resources\StorageOptionPartResource;
use App\Http\Resources\StorageOptionResource;
use App\Models\StorageOption;
use App\Models\StorageOptionPart;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StorageOptionController extends Controller
{
    public function __construct(
        private readonly CreateStorageOptionAction $createStorageOptionAction,
        private readonly UpdateStorageOptionAction $updateStorageOptionAction,
        private readonly DeleteStorageOptionAction $deleteStorageOptionAction,
        private readonly CreateStorageOptionPartAction $createStorageOptionPartAction,
        private readonly DeleteStorageOptionPartAction $deleteStorageOptionPartAction,
    ) {}

    public function index(#[CurrentUser] User $user): AnonymousResourceCollection
    {
        $storageOptions = StorageOption::where('family_id', $user->family_id)
            ->whereNull('parent_id')
            ->with('children')
            ->get();

        return StorageOptionResource::collection($storageOptions);
    }

    public function store(StoreStorageOptionRequest $request): StorageOptionResource
    {
        $storageOption = $this->createStorageOptionAction->execute($request);

        return new StorageOptionResource($storageOption);
    }

    public function show(#[CurrentUser] User $user, StorageOption $storageOption): StorageOptionResource|JsonResponse
    {
        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $storageOption->load('children');

        return new StorageOptionResource($storageOption);
    }

    public function update(
        UpdateStorageOptionRequest $request,
        StorageOption $storageOption,
        #[CurrentUser] User $user,
    ): StorageOptionResource|JsonResponse {
        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $storageOption = $this->updateStorageOptionAction->execute($storageOption, $request);

        return new StorageOptionResource($storageOption);
    }

    public function destroy(#[CurrentUser] User $user, StorageOption $storageOption): JsonResponse
    {
        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $this->deleteStorageOptionAction->execute($storageOption);

        return response()->json(null, 204);
    }

    public function parts(#[CurrentUser] User $user, StorageOption $storageOption): AnonymousResourceCollection|JsonResponse
    {
        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $parts = $storageOption->storageOptionParts()->with(['part', 'color'])->get();

        return StorageOptionPartResource::collection($parts);
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

        $resource = new StorageOptionPartResource($storageOptionPart);

        return $storageOptionPart->wasRecentlyCreated
            ? $resource->response()->setStatusCode(201)
            : $resource->response()->setStatusCode(200);
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

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StorageOption\AssignPartToStorageAction;
use App\Actions\StorageOption\CreateStorageOptionAction;
use App\Actions\StorageOption\DeleteStorageOptionAction;
use App\Actions\StorageOption\RemovePartFromStorageAction;
use App\Actions\StorageOption\UpdateStorageOptionAction;
use App\DataTransferObjects\AssignPartToStorageData;
use App\DataTransferObjects\CreateStorageOptionData;
use App\DataTransferObjects\UpdateStorageOptionData;
use App\Http\Requests\StorageOption\AssignPartRequest;
use App\Http\Requests\StorageOption\StoreStorageOptionRequest;
use App\Http\Requests\StorageOption\UpdateStorageOptionRequest;
use App\Http\Resources\StorageOptionPartResourceData;
use App\Http\Resources\StorageOptionResourceData;
use App\Models\StorageOption;
use App\Models\StorageOptionPart;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorageOptionController extends Controller
{
    public function __construct(
        private readonly CreateStorageOptionAction $createStorageOptionAction,
        private readonly UpdateStorageOptionAction $updateStorageOptionAction,
        private readonly DeleteStorageOptionAction $deleteStorageOptionAction,
        private readonly AssignPartToStorageAction $assignPartToStorageAction,
        private readonly RemovePartFromStorageAction $removePartFromStorageAction,
    ) {}

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function index(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();

        $storageOptions = StorageOption::where('family_id', $user->family_id)
            ->whereNull('parent_id')
            ->with('children')
            ->get();

        return StorageOptionResourceData::collection($storageOptions);
    }

    public function store(StoreStorageOptionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array{name: string, description: ?string, parent_id: ?int, row: ?int, column: ?int} $validated */
        $validated = $request->validated();

        $data = new CreateStorageOptionData(
            familyId: $user->family_id,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            parentId: $validated['parent_id'] ?? null,
            row: $validated['row'] ?? null,
            column: $validated['column'] ?? null,
        );

        $storageOption = $this->createStorageOptionAction->execute($data);

        return StorageOptionResourceData::from($storageOption)->toResponseWithStatus(201);
    }

    public function show(Request $request, StorageOption $storageOption): StorageOptionResourceData|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $storageOption->load('children');

        return StorageOptionResourceData::from($storageOption);
    }

    public function update(UpdateStorageOptionRequest $request, StorageOption $storageOption): StorageOptionResourceData|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        /** @var array{name: string, description: ?string, parent_id: ?int, row: ?int, column: ?int} $validated */
        $validated = $request->validated();

        $data = new UpdateStorageOptionData(
            name: $validated['name'],
            description: $validated['description'] ?? null,
            parentId: $validated['parent_id'] ?? null,
            row: $validated['row'] ?? null,
            column: $validated['column'] ?? null,
        );

        $storageOption = $this->updateStorageOptionAction->execute($storageOption, $data);

        return StorageOptionResourceData::from($storageOption);
    }

    public function destroy(Request $request, StorageOption $storageOption): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $this->deleteStorageOptionAction->execute($storageOption);

        return response()->json(null, 204);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>|JsonResponse
     */
    public function parts(Request $request, StorageOption $storageOption): array|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $parts = $storageOption->storageOptionParts()->with(['part', 'color'])->get();

        return StorageOptionPartResourceData::collection($parts);
    }

    public function assignPart(AssignPartRequest $request, StorageOption $storageOption): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        /** @var array{part_id: int, color_id: ?int, quantity: int} $validated */
        $validated = $request->validated();

        $data = new AssignPartToStorageData(
            storageOptionId: $storageOption->id,
            partId: $validated['part_id'],
            colorId: $validated['color_id'] ?? null,
            quantity: $validated['quantity'],
        );

        $storageOptionPart = $this->assignPartToStorageAction->execute($data);
        $storageOptionPart->load(['part', 'color']);

        $resource = StorageOptionPartResourceData::from($storageOptionPart);
        $statusCode = $storageOptionPart->wasRecentlyCreated ? 201 : 200;

        return $resource->toResponseWithStatus($statusCode);
    }

    public function removePart(Request $request, StorageOption $storageOption, StorageOptionPart $part): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($storageOption->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($part->storage_option_id !== $storageOption->id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $this->removePartFromStorageAction->execute($part);

        return response()->json(null, 204);
    }
}

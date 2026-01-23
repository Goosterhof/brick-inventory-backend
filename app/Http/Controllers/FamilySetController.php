<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FamilySet\AddSetToFamilyAction;
use App\Actions\FamilySet\RemoveFamilySetAction;
use App\Actions\FamilySet\UpdateFamilySetAction;
use App\DataTransferObjects\CreateFamilySetData;
use App\DataTransferObjects\UpdateFamilySetData;
use App\Enums\FamilySetStatus;
use App\Http\Requests\StoreFamilySetRequest;
use App\Http\Requests\UpdateFamilySetRequest;
use App\Http\Resources\FamilySetResourceData;
use App\Models\FamilySet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;

class FamilySetController extends Controller
{
    public function __construct(
        private readonly AddSetToFamilyAction $addSetToFamilyAction,
        private readonly UpdateFamilySetAction $updateFamilySetAction,
        private readonly RemoveFamilySetAction $removeFamilySetAction,
    ) {}

    /**
     * @return array<string, array<int, FamilySetResourceData>>
     */
    public function index(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $familySets = FamilySet::where('family_id', $user->family_id)
            ->with('set')
            ->orderBy('created_at', 'desc')
            ->get();

        return ['data' => FamilySetResourceData::collection($familySets)];
    }

    public function store(StoreFamilySetRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var array{set_num: string, quantity?: int, status?: string, purchase_date?: string|null, notes?: string|null} $validated */
        $validated = $request->validated();

        $data = new CreateFamilySetData(
            setNum: $validated['set_num'],
            quantity: $validated['quantity'] ?? 1,
            status: isset($validated['status']) ? FamilySetStatus::from($validated['status']) : FamilySetStatus::Sealed,
            purchaseDate: isset($validated['purchase_date']) ? Carbon::parse($validated['purchase_date']) : null,
            notes: $validated['notes'] ?? null,
        );

        try {
            $family = $user->family;

            if ($family === null) {
                return response()->json(['error' => 'User does not belong to a family'], 400);
            }

            $familySet = $this->addSetToFamilyAction->execute($family, $data);

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

    public function show(FamilySet $familySet): FamilySetResourceData|JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($familySet->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $familySet->load('set');

        return FamilySetResourceData::from($familySet);
    }

    public function update(UpdateFamilySetRequest $request, FamilySet $familySet): FamilySetResourceData|JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($familySet->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        /** @var array{quantity: int, status: string, purchase_date?: string|null, notes?: string|null} $validated */
        $validated = $request->validated();

        $data = new UpdateFamilySetData(
            quantity: $validated['quantity'],
            status: FamilySetStatus::from($validated['status']),
            purchaseDate: isset($validated['purchase_date']) ? Carbon::parse($validated['purchase_date']) : null,
            notes: $validated['notes'] ?? null,
        );

        $familySet = $this->updateFamilySetAction->execute($familySet, $data);
        $familySet->load('set');

        return FamilySetResourceData::from($familySet);
    }

    public function destroy(FamilySet $familySet): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($familySet->family_id !== $user->family_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $this->removeFamilySetAction->execute($familySet);

        return response()->json(null, 204);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Family\GetFamilyStatsAction;
use App\Actions\Family\SetRebrickableTokenAction;
use App\Http\Requests\Family\SetRebrickableTokenRequest;
use App\Http\Resources\FamilyStatsResourceData;
use App\Models\Family;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\JsonResponse;

class FamilyController extends Controller
{
    public function __construct(
        private readonly Gate $gate,
        private readonly GetFamilyStatsAction $getFamilyStatsAction,
        private readonly SetRebrickableTokenAction $setRebrickableTokenAction,
    ) {}

    public function stats(#[CurrentUser] User $user): JsonResponse
    {
        $this->gate->authorize('viewStats', Family::class);

        $familyStatsData = $this->getFamilyStatsAction->execute($user->family);

        return FamilyStatsResourceData::from($familyStatsData)->toResponse();
    }

    public function setRebrickableToken(
        SetRebrickableTokenRequest $setRebrickableTokenRequest,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $this->gate->authorize('setRebrickableToken');

        $this->setRebrickableTokenAction->execute($user->family, $setRebrickableTokenRequest->toDto(), $user);

        return response()->json(status: 204);
    }
}

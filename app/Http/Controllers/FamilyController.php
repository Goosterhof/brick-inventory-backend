<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Family\GetFamilyPartsAction;
use App\Actions\Family\GetFamilyStatsAction;
use App\Actions\Family\SetRebrickableTokenAction;
use App\Http\Requests\Family\SetRebrickableTokenRequest;
use App\Http\Resources\FamilyMemberResourceData;
use App\Http\Resources\FamilyStatsResourceData;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class FamilyController extends Controller
{
    public function members(
        #[CurrentUser] User $user,
    ): JsonResponse {
        return new JsonResponse(FamilyMemberResourceData::fromFamily($user->family));
    }

    public function parts(
        #[CurrentUser] User $user,
        GetFamilyPartsAction $getFamilyPartsAction,
    ): JsonResponse {
        return new JsonResponse($getFamilyPartsAction->execute($user->family));
    }

    public function stats(
        #[CurrentUser] User $user,
        GetFamilyStatsAction $getFamilyStatsAction,
    ): JsonResponse {
        $familyStatsData = $getFamilyStatsAction->execute($user->family);

        return FamilyStatsResourceData::from($familyStatsData)->toResponse();
    }

    public function setRebrickableToken(
        SetRebrickableTokenRequest $setRebrickableTokenRequest,
        #[CurrentUser] User $user,
        SetRebrickableTokenAction $setRebrickableTokenAction,
    ): JsonResponse {
        $setRebrickableTokenAction->execute($user->family, $setRebrickableTokenRequest->toDto(), $user);

        return response()->json(status: 204);
    }
}

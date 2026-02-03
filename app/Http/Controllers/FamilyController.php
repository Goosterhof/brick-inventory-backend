<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Family\SetRebrickableTokenAction;
use App\Http\Requests\Family\SetRebrickableTokenRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class FamilyController extends Controller
{
    public function __construct(
        private readonly SetRebrickableTokenAction $setRebrickableTokenAction,
    ) {}

    public function setRebrickableToken(
        SetRebrickableTokenRequest $setRebrickableTokenRequest,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $this->setRebrickableTokenAction->execute($user->family, $setRebrickableTokenRequest, $user);

        return response()->json(status: 204);
    }
}

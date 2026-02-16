<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\ProfileResourceData;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(
        private readonly LoginUserAction $loginUserAction,
        private readonly StatefulGuard $statefulGuard,
    ) {}

    public function __invoke(LoginRequest $loginRequest): JsonResponse
    {
        $user = $this->loginUserAction->execute($loginRequest->toDto());

        $this->statefulGuard->login($user);

        return response()->json(ProfileResourceData::from($user));
    }
}

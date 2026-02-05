<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\ProfileResourceData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(
        private readonly LoginUserAction $loginUserAction,
    ) {}

    public function __invoke(LoginRequest $loginRequest): JsonResponse
    {
        $user = $this->loginUserAction->execute($loginRequest);

        Auth::login($user);

        return response()->json(ProfileResourceData::from($user));
    }
}

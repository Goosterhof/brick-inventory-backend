<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterUserWithFamilyAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(
        private readonly RegisterUserWithFamilyAction $registerUserWithFamilyAction,
    ) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = $this->registerUserWithFamilyAction->execute($request);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }
}

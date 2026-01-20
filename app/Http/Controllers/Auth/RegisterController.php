<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterUserWithFamilyAction;
use App\DataTransferObjects\RegisterUserData;
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
        /** @var array{family_name: string, name: string, email: string, password: string} $validated */
        $validated = $request->validated();

        $data = new RegisterUserData(
            familyName: $validated['family_name'],
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
        );

        $user = $this->registerUserWithFamilyAction->execute($data);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }
}

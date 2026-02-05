<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CreateUserWithFamilyAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\ProfileResourceData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function __construct(
        private readonly CreateUserWithFamilyAction $createUserWithFamilyAction,
    ) {}

    public function __invoke(RegisterRequest $registerRequest): JsonResponse
    {
        $user = $this->createUserWithFamilyAction->execute($registerRequest);

        Auth::login($user);

        return response()->json(ProfileResourceData::from($user), 201);
    }
}

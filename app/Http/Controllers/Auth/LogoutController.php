<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class LogoutController extends Controller
{
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $user->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}

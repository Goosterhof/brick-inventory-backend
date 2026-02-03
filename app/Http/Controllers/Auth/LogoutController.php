<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $user->currentAccessToken()->delete();
        }

        return response()->noContent();
    }
}

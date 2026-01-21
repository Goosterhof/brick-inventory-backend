<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetSetPartsAction;
use App\Http\Resources\SetPartsResource;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;

class SetController extends Controller
{
    public function __construct(
        private readonly GetSetPartsAction $getSetPartsAction,
    ) {}

    public function parts(string $setNum): SetPartsResource|JsonResponse
    {
        try {
            $result = $this->getSetPartsAction->execute($setNum);

            return new SetPartsResource($result);
        } catch (RequestException $requestException) {
            $status = $requestException->response->status();
            $message = match ($status) {
                404 => 'Set not found',
                401 => 'Invalid API key',
                default => 'Failed to fetch set data',
            };

            return response()->json(['error' => $message], $status);
        }
    }
}

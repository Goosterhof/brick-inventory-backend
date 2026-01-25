<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetSetPartsAction;
use App\Http\Resources\SetWithPartsResourceData;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;

class SetController extends Controller
{
    public function __construct(
        private readonly GetSetPartsAction $getSetPartsAction,
    ) {}

    public function parts(string $setNum): SetWithPartsResourceData|JsonResponse
    {
        try {
            $set = $this->getSetPartsAction->execute($setNum);

            return SetWithPartsResourceData::from($set);
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

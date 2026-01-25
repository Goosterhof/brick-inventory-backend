<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetSetPartsAction;
use App\Exceptions\RebrickableApiException;
use App\Exceptions\SetNotFoundException;
use App\Http\Resources\SetWithPartsResourceData;
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
        } catch (SetNotFoundException) {
            return response()->json(['error' => 'Set not found'], 404);
        } catch (RebrickableApiException $exception) {
            $status = $exception->statusCode ?? 500;
            $message = match ($status) {
                401 => 'Invalid API key',
                default => 'Failed to fetch set data',
            };

            return response()->json(['error' => $message], $status);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetSetPartsAction;
use App\Http\Resources\SetWithPartsResourceData;
use Illuminate\Http\JsonResponse;

class SetController extends Controller
{
    public function __construct(
        private readonly GetSetPartsAction $getSetPartsAction,
    ) {}

    public function parts(string $setNum): JsonResponse
    {
        $set = $this->getSetPartsAction->execute($setNum);

        return SetWithPartsResourceData::from($set)->toResponse();
    }
}

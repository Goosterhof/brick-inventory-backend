<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BrickIdentification\IdentifyBrickAction;
use App\Http\Requests\BrickIdentification\IdentifyBrickRequest;
use App\Http\Resources\PartResourceData;
use Illuminate\Http\JsonResponse;

class BrickIdentificationController extends Controller
{
    public function __construct(
        private readonly IdentifyBrickAction $identifyBrickAction,
    ) {}

    public function identify(IdentifyBrickRequest $identifyBrickRequest): JsonResponse
    {
        $part = $this->identifyBrickAction->execute($identifyBrickRequest);

        return PartResourceData::from($part)->toResponse();
    }
}

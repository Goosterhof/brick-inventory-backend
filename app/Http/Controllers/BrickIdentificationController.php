<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BrickIdentification\IdentifyBrickAction;
use App\Http\Requests\BrickIdentification\IdentifyBrickRequest;
use App\Http\Resources\PartResourceData;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\JsonResponse;

class BrickIdentificationController extends Controller
{
    public function __construct(
        private readonly Gate $gate,
        private readonly IdentifyBrickAction $identifyBrickAction,
    ) {}

    public function identify(IdentifyBrickRequest $identifyBrickRequest): JsonResponse
    {
        $this->gate->authorize('identify');

        $part = $this->identifyBrickAction->execute($identifyBrickRequest->toDto());

        return PartResourceData::from($part)->toResponse();
    }
}

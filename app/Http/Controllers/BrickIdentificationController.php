<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BrickIdentification\IdentifyBrickAction;
use App\Http\Requests\BrickIdentification\IdentifyBrickRequest;
use App\Http\Resources\PartResourceData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class BrickIdentificationController extends Controller
{
    public function __construct(
        private readonly IdentifyBrickAction $identifyBrickAction,
    ) {}

    public function identify(IdentifyBrickRequest $identifyBrickRequest): JsonResponse
    {
        /** @var UploadedFile $image */
        $image = $identifyBrickRequest->file(IdentifyBrickRequest::IMAGE);

        $part = $this->identifyBrickAction->execute($image);

        return PartResourceData::from($part)->toResponse();
    }
}

<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\Brickognize\BrickognizePredictionData;
use App\Exceptions\BrickognizeApiException;
use Illuminate\Http\UploadedFile;

interface BrickIdentificationServiceInterface
{
    /**
     * Identify a LEGO brick from an uploaded image.
     *
     * @throws BrickognizeApiException
     *
     * @return list<BrickognizePredictionData>
     */
    public function identifyBrick(UploadedFile $uploadedFile): array;
}

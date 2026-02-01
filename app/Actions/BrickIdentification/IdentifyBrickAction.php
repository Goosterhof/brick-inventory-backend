<?php

declare(strict_types=1);

namespace App\Actions\BrickIdentification;

use App\Contracts\BrickIdentificationServiceInterface;
use App\Data\Brickognize\BrickognizePredictionData;
use App\Exceptions\BrickognizeApiException;
use App\Exceptions\PartNotFoundException;
use App\Models\Part;
use Illuminate\Http\UploadedFile;

class IdentifyBrickAction
{
    public function __construct(
        private readonly BrickIdentificationServiceInterface $brickIdentificationService,
        private readonly Part $part,
    ) {}

    /**
     * Identify a LEGO brick from an image and return the matching part from the database.
     *
     * @throws BrickognizeApiException
     * @throws PartNotFoundException
     */
    public function execute(UploadedFile $uploadedFile): Part
    {
        $predictions = $this->brickIdentificationService->identifyBrick($uploadedFile);

        // Filter for part predictions only (exclude minifigs, sets, etc.)
        $partPredictions = array_filter(
            $predictions,
            static fn (BrickognizePredictionData $brickognizePredictionData): bool => $brickognizePredictionData->type === 'part',
        );

        if ($partPredictions === []) {
            throw BrickognizeApiException::noItemsFound();
        }

        // Get the highest scoring part prediction
        $bestPrediction = null;
        foreach ($partPredictions as $partPrediction) {
            if ($bestPrediction === null || $partPrediction->score > $bestPrediction->score) {
                $bestPrediction = $partPrediction;
            }
        }

        /** @var BrickognizePredictionData $bestPrediction */

        // Look up the part in our database
        $part = $this->part->newQuery()
            ->where('part_num', $bestPrediction->id)
            ->first();

        if (!$part instanceof Part) {
            throw PartNotFoundException::forPartNum($bestPrediction->id);
        }

        return $part;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\BrickIdentification;

use App\Contracts\BrickIdentification\IdentifyBrickInterface;
use App\Contracts\BrickIdentificationServiceInterface;
use App\Data\Brickognize\BrickognizePredictionData;
use App\Exceptions\BrickognizeApiException;
use App\Exceptions\PartNotFoundException;
use App\Models\Part;

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
    public function execute(IdentifyBrickInterface $identifyBrick): Part
    {
        $predictions = $this->brickIdentificationService->identifyBrick($identifyBrick->image);

        // Filter for part predictions only (exclude minifigs, sets, etc.)
        $partPredictions = array_filter(
            $predictions,
            static fn (BrickognizePredictionData $brickognizePredictionData): bool => $brickognizePredictionData->type === 'part',
        );

        if ($partPredictions === []) {
            throw BrickognizeApiException::noItemsFound();
        }

        // Get the highest scoring part prediction
        $bestPrediction = array_reduce(
            $partPredictions,
            static function (?BrickognizePredictionData $carry, BrickognizePredictionData $item): BrickognizePredictionData {
                if ($carry === null || $item->score > $carry->score) {
                    return $item;
                }

                return $carry;
            },
            null
        );

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

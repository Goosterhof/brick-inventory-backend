<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\DataTransferObjects\FamilySet\UpdateFamilySetData;
use App\Models\FamilySet;
use DateTimeInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\DateFactory;

final readonly class UpdateFamilySetAction
{
    public function __construct(
        private DateFactory $dateFactory,
        private ConnectionInterface $connection,
    ) {}

    public function execute(FamilySet $familySet, UpdateFamilySetData $updateFamilySetData): FamilySet
    {
        return $this->connection->transaction(function () use ($familySet, $updateFamilySetData): FamilySet {
            $familySet->quantity = $updateFamilySetData->quantity;
            $familySet->status = $updateFamilySetData->status;
            $familySet->purchase_date = $updateFamilySetData->purchaseDate instanceof DateTimeInterface
                ? $this->dateFactory->instance($updateFamilySetData->purchaseDate)
                : null;
            $familySet->notes = $updateFamilySetData->notes;
            $familySet->save();

            return $familySet;
        });
    }
}

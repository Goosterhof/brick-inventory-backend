<?php

declare(strict_types = 1);

namespace App\Actions\Sync;

use App\DataTransferObjects\Input\Lego\LegoSetData;
use App\Models\Set;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class UpsertSetAction
{
    public function __construct(
        private Set $set,
        private ConnectionInterface $connection,
    ) {}

    public function execute(LegoSetData $legoSetData): Set
    {
        try {
            return $this->connection->transaction(function() use ($legoSetData): Set {
                $set = $this->set->newQuery()->where('set_num', $legoSetData->setNum)->first();

                if (!$set instanceof Set) {
                    /** @var Set $set */
                    $set = $this->set->newInstance();
                    $set->set_num = $legoSetData->setNum;
                }

                $set->name = $legoSetData->name;
                $set->year = $legoSetData->year;
                $set->theme = $legoSetData->themeId !== null ? (string) $legoSetData->themeId : null;
                $set->num_parts = $legoSetData->numParts;
                $set->image_url = $legoSetData->imageUrl;
                $set->save();

                return $set;
            });
        } catch (UniqueConstraintViolationException) {
            return $this->connection->transaction(function() use ($legoSetData): Set {
                /** @var Set */
                $set = $this->set->newQuery()->where('set_num', $legoSetData->setNum)->firstOrFail();

                $set->name = $legoSetData->name;
                $set->year = $legoSetData->year;
                $set->theme = $legoSetData->themeId !== null ? (string) $legoSetData->themeId : null;
                $set->num_parts = $legoSetData->numParts;
                $set->image_url = $legoSetData->imageUrl;
                $set->save();

                return $set;
            });
        }
    }
}

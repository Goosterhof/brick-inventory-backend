<?php

declare(strict_types = 1);

namespace App\Actions\StorageOption;

use App\DataTransferObjects\StorageOption\StorageOptionData;
use App\Models\Family;
use App\Models\StorageOption;
use Illuminate\Database\ConnectionInterface;

final readonly class CreateStorageOptionAction
{
    public function __construct(
        private StorageOption $storageOption,
        private ConnectionInterface $connection,
    ) {}

    public function execute(Family $family, StorageOptionData $storageOptionData): StorageOption
    {
        return $this->connection->transaction(function() use ($family, $storageOptionData): StorageOption {
            $storageOption = $this->storageOption->newInstance();
            $storageOption->family_id = $family->id;
            $storageOption->name = $storageOptionData->name;
            $storageOption->description = $storageOptionData->description;
            $storageOption->parent_id = $storageOptionData->parentId;
            $storageOption->row = $storageOptionData->row;
            $storageOption->column = $storageOptionData->column;
            $storageOption->save();

            return $storageOption;
        });
    }
}

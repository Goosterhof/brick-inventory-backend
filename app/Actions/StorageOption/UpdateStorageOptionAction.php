<?php

declare(strict_types = 1);

namespace App\Actions\StorageOption;

use App\DataTransferObjects\Input\StorageOption\StorageOptionData;
use App\Models\StorageOption;
use Illuminate\Database\ConnectionInterface;

final readonly class UpdateStorageOptionAction
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    public function execute(StorageOption $storageOption, StorageOptionData $storageOptionData): StorageOption
    {
        return $this->connection->transaction(function() use ($storageOption, $storageOptionData): StorageOption {
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

<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\Contracts\StorageOption\StorageOptionDataInterface;
use App\Models\StorageOption;

class UpdateStorageOptionAction
{
    public function execute(StorageOption $storageOption, StorageOptionDataInterface $storageOptionData): StorageOption
    {
        $storageOption->name = $storageOptionData->name;
        $storageOption->description = $storageOptionData->description;
        $storageOption->parent_id = $storageOptionData->parentId;
        $storageOption->row = $storageOptionData->row;
        $storageOption->column = $storageOptionData->column;
        $storageOption->save();

        return $storageOption;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\Contracts\StorageOption\UpdateStorageOptionInterface;
use App\Models\StorageOption;

class UpdateStorageOptionAction
{
    public function execute(StorageOption $storageOption, UpdateStorageOptionInterface $updateStorageOption): StorageOption
    {
        $storageOption->name = $updateStorageOption->name;
        $storageOption->description = $updateStorageOption->description;
        $storageOption->parent_id = $updateStorageOption->parentId;
        $storageOption->row = $updateStorageOption->row;
        $storageOption->column = $updateStorageOption->column;
        $storageOption->save();

        return $storageOption;
    }
}

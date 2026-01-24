<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\Contracts\StorageOption\UpdateStorageOptionInterface;
use App\Models\StorageOption;

class UpdateStorageOptionAction
{
    public function execute(StorageOption $storageOption, UpdateStorageOptionInterface $data): StorageOption
    {
        $storageOption->name = $data->name;
        $storageOption->description = $data->description;
        $storageOption->parent_id = $data->parentId;
        $storageOption->row = $data->row;
        $storageOption->column = $data->column;
        $storageOption->save();

        return $storageOption;
    }
}

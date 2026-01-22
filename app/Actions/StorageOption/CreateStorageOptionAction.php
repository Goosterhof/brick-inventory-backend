<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\DataTransferObjects\CreateStorageOptionData;
use App\Models\StorageOption;

class CreateStorageOptionAction
{
    public function __construct(
        private readonly StorageOption $storageOption,
    ) {}

    public function execute(CreateStorageOptionData $data): StorageOption
    {
        $storageOption = $this->storageOption->newInstance();
        $storageOption->family_id = $data->familyId;
        $storageOption->name = $data->name;
        $storageOption->description = $data->description;
        $storageOption->parent_id = $data->parentId;
        $storageOption->row = $data->row;
        $storageOption->column = $data->column;
        $storageOption->save();

        return $storageOption;
    }
}

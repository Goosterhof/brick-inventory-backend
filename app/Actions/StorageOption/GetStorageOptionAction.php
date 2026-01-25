<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\Models\StorageOption;

class GetStorageOptionAction
{
    public function execute(StorageOption $storageOption): StorageOption
    {
        $storageOption->load('children');

        return $storageOption;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\Models\StorageOption;

class DeleteStorageOptionAction
{
    public function execute(StorageOption $storageOption): void
    {
        $storageOption->delete();
    }
}

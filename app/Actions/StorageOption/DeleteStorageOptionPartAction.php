<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\Models\StorageOptionPart;

final readonly class DeleteStorageOptionPartAction
{
    public function execute(StorageOptionPart $storageOptionPart): void
    {
        $storageOptionPart->delete();
    }
}

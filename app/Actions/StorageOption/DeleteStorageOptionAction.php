<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\Models\StorageOption;

final readonly class DeleteStorageOptionAction
{
    public function execute(StorageOption $storageOption): void
    {
        // Recursively delete children first
        foreach ($storageOption->children as $child) {
            $this->execute($child);
        }

        // Delete associated storage option parts
        $storageOption->storageOptionParts()->delete();

        // Delete the storage option
        $storageOption->delete();
    }
}

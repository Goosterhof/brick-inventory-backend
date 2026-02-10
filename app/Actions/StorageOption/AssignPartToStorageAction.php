<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\DataTransferObjects\StorageOption\AssignPartToStorageData;
use App\Models\StorageOption;
use App\Models\StorageOptionPart;

final readonly class AssignPartToStorageAction
{
    public function __construct(
        private StorageOptionPart $storageOptionPart,
    ) {}

    public function execute(StorageOption $storageOption, AssignPartToStorageData $assignPartToStorageData): StorageOptionPart
    {
        $storageOptionPart = $this->storageOptionPart->newQuery()
            ->where('storage_option_id', $storageOption->id)
            ->where('part_id', $assignPartToStorageData->partId)
            ->where('color_id', $assignPartToStorageData->colorId)
            ->first();

        if ($storageOptionPart === null) {
            $storageOptionPart = $this->storageOptionPart->newInstance();
            $storageOptionPart->storage_option_id = $storageOption->id;
            $storageOptionPart->part_id = $assignPartToStorageData->partId;
            $storageOptionPart->color_id = $assignPartToStorageData->colorId;
        }

        $storageOptionPart->quantity = $assignPartToStorageData->quantity;
        $storageOptionPart->save();

        return $storageOptionPart;
    }
}

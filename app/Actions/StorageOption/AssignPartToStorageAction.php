<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\DataTransferObjects\AssignPartToStorageData;
use App\Models\StorageOptionPart;

class AssignPartToStorageAction
{
    public function __construct(
        private readonly StorageOptionPart $storageOptionPart,
    ) {}

    public function execute(AssignPartToStorageData $data): StorageOptionPart
    {
        $storageOptionPart = $this->storageOptionPart->newQuery()->firstOrNew([
            'storage_option_id' => $data->storageOptionId,
            'part_id' => $data->partId,
            'color_id' => $data->colorId,
        ]);

        $storageOptionPart->quantity = $data->quantity;
        $storageOptionPart->save();

        return $storageOptionPart;
    }
}

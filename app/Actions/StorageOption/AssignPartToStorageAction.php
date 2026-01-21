<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\DataTransferObjects\AssignPartToStorageData;
use App\Models\StorageOptionPart;

class AssignPartToStorageAction
{
    public function execute(AssignPartToStorageData $data): StorageOptionPart
    {
        return StorageOptionPart::updateOrCreate(
            [
                'storage_option_id' => $data->storageOptionId,
                'part_id' => $data->partId,
                'color_id' => $data->colorId,
            ],
            [
                'quantity' => $data->quantity,
            ],
        );
    }
}

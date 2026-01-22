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
        $storageOptionPart = $this->storageOptionPart->newQuery()
            ->where('storage_option_id', $data->storageOptionId)
            ->where('part_id', $data->partId)
            ->where('color_id', $data->colorId)
            ->first();

        if ($storageOptionPart === null) {
            $storageOptionPart = $this->storageOptionPart->newInstance();
            $storageOptionPart->storage_option_id = $data->storageOptionId;
            $storageOptionPart->part_id = $data->partId;
            $storageOptionPart->color_id = $data->colorId;
        }

        $storageOptionPart->quantity = $data->quantity;
        $storageOptionPart->save();

        return $storageOptionPart;
    }
}

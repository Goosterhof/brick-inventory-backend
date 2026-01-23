<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\Contracts\StorageOption\AssignPartToStorageInterface;
use App\Models\StorageOption;
use App\Models\StorageOptionPart;

class AssignPartToStorageAction
{
    public function __construct(
        private readonly StorageOptionPart $storageOptionPart,
    ) {}

    public function execute(StorageOption $storageOption, AssignPartToStorageInterface $data): StorageOptionPart
    {
        $storageOptionPart = $this->storageOptionPart->newQuery()
            ->where('storage_option_id', $storageOption->id)
            ->where('part_id', $data->partId)
            ->where('color_id', $data->colorId)
            ->first();

        if ($storageOptionPart === null) {
            $storageOptionPart = $this->storageOptionPart->newInstance();
            $storageOptionPart->storage_option_id = $storageOption->id;
            $storageOptionPart->part_id = $data->partId;
            $storageOptionPart->color_id = $data->colorId;
        }

        $storageOptionPart->quantity = $data->quantity;
        $storageOptionPart->save();

        return $storageOptionPart;
    }
}

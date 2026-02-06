<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\Contracts\StorageOption\AssignPartToStorageInterface;
use App\Models\StorageOption;
use App\Models\StorageOptionPart;

final readonly class AssignPartToStorageAction
{
    public function __construct(
        private StorageOptionPart $storageOptionPart,
    ) {}

    public function execute(StorageOption $storageOption, AssignPartToStorageInterface $assignPartToStorage): StorageOptionPart
    {
        $storageOptionPart = $this->storageOptionPart->newQuery()
            ->where('storage_option_id', $storageOption->id)
            ->where('part_id', $assignPartToStorage->partId)
            ->where('color_id', $assignPartToStorage->colorId)
            ->first();

        if ($storageOptionPart === null) {
            $storageOptionPart = $this->storageOptionPart->newInstance();
            $storageOptionPart->storage_option_id = $storageOption->id;
            $storageOptionPart->part_id = $assignPartToStorage->partId;
            $storageOptionPart->color_id = $assignPartToStorage->colorId;
        }

        $storageOptionPart->quantity = $assignPartToStorage->quantity;
        $storageOptionPart->save();

        return $storageOptionPart;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\Contracts\StorageOption\StorageOptionDataInterface;
use App\Models\StorageOption;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class CreateStorageOptionAction
{
    public function __construct(
        private readonly StorageOption $storageOption,
        #[CurrentUser]
        private readonly User $user,
    ) {}

    public function execute(StorageOptionDataInterface $storageOptionData): StorageOption
    {
        $storageOption = $this->storageOption->newInstance();
        $storageOption->family_id = $this->user->family_id;
        $storageOption->name = $storageOptionData->name;
        $storageOption->description = $storageOptionData->description;
        $storageOption->parent_id = $storageOptionData->parentId;
        $storageOption->row = $storageOptionData->row;
        $storageOption->column = $storageOptionData->column;
        $storageOption->save();

        return $storageOption;
    }
}

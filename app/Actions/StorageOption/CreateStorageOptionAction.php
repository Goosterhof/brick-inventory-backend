<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\Contracts\StorageOption\CreateStorageOptionInterface;
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

    public function execute(CreateStorageOptionInterface $createStorageOption): StorageOption
    {
        $storageOption = $this->storageOption->newInstance();
        $storageOption->family_id = $this->user->family_id;
        $storageOption->name = $createStorageOption->name;
        $storageOption->description = $createStorageOption->description;
        $storageOption->parent_id = $createStorageOption->parentId;
        $storageOption->row = $createStorageOption->row;
        $storageOption->column = $createStorageOption->column;
        $storageOption->save();

        return $storageOption;
    }
}

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

    public function execute(CreateStorageOptionInterface $data): StorageOption
    {
        $storageOption = $this->storageOption->newInstance();
        $storageOption->family_id = $this->user->family_id;
        $storageOption->name = $data->name;
        $storageOption->description = $data->description;
        $storageOption->parent_id = $data->parentId;
        $storageOption->row = $data->row;
        $storageOption->column = $data->column;
        $storageOption->save();

        return $storageOption;
    }
}

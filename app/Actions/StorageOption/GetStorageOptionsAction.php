<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\Models\StorageOption;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final readonly class GetStorageOptionsAction
{
    public function __construct(
        private StorageOption $storageOption,
    ) {}

    /**
     * @return Collection<int, StorageOption>
     */
    public function execute(User $user): Collection
    {
        return $this->storageOption->newQuery()
            ->where('family_id', $user->family_id)
            ->whereNull('parent_id')
            ->get();
    }
}

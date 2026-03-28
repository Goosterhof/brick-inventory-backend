<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\Models\StorageOption;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;

final readonly class GetStorageOptionsAction
{
    private const int DEFAULT_PER_PAGE = 25;

    private const int MAX_PER_PAGE = 100;

    public function __construct(
        private StorageOption $storageOption,
    ) {}

    /**
     * @return CursorPaginator<int, StorageOption>
     */
    public function execute(User $user, int $perPage = self::DEFAULT_PER_PAGE, ?string $cursor = null): CursorPaginator
    {
        return $this->storageOption->newQuery()
            ->where('family_id', $user->family_id)
            ->whereNull('parent_id')
            ->orderBy('id')
            ->cursorPaginate(
                perPage: min($perPage, self::MAX_PER_PAGE),
                cursor: $cursor,
            );
    }
}

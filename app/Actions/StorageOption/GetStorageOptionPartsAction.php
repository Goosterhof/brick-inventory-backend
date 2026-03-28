<?php

declare(strict_types=1);

namespace App\Actions\StorageOption;

use App\Models\StorageOption;
use App\Models\StorageOptionPart;
use Illuminate\Pagination\CursorPaginator;

final readonly class GetStorageOptionPartsAction
{
    private const int DEFAULT_PER_PAGE = 25;

    private const int MAX_PER_PAGE = 100;

    /**
     * @return CursorPaginator<int, StorageOptionPart>
     */
    public function execute(StorageOption $storageOption, int $perPage = self::DEFAULT_PER_PAGE, ?string $cursor = null): CursorPaginator
    {
        return $storageOption->storageOptionParts()
            ->orderBy('id')
            ->cursorPaginate(min($perPage, self::MAX_PER_PAGE), ['*'], 'cursor', $cursor);
    }
}

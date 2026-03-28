<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\Models\FamilySet;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;

final readonly class GetFamilySetsAction
{
    private const int DEFAULT_PER_PAGE = 25;

    private const int MAX_PER_PAGE = 100;

    public function __construct(
        private FamilySet $familySet,
    ) {}

    /**
     * @return CursorPaginator<int, FamilySet>
     */
    public function execute(User $user, int $perPage = self::DEFAULT_PER_PAGE, ?string $cursor = null): CursorPaginator
    {
        return $this->familySet->newQuery()
            ->where('family_id', $user->family_id)
            ->orderByDesc('id')
            ->cursorPaginate(
                perPage: min($perPage, self::MAX_PER_PAGE),
                cursor: $cursor,
            );
    }
}

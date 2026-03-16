<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final readonly class GetFamilyMembersAction
{
    public function __construct(
        private User $user,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function execute(Family $family): Collection
    {
        return $this->user->newQuery()
            ->where('family_id', $family->id)
            ->get();
    }
}

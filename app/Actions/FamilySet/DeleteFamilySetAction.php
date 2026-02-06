<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\Models\FamilySet;

final readonly class DeleteFamilySetAction
{
    public function execute(FamilySet $familySet): void
    {
        $familySet->delete();
    }
}

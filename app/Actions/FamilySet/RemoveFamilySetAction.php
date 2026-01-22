<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\Models\FamilySet;

class RemoveFamilySetAction
{
    public function execute(FamilySet $familySet): void
    {
        $familySet->delete();
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\FamilySet;

use App\Models\FamilySet;

class GetFamilySetAction
{
    public function execute(FamilySet $familySet): FamilySet
    {
        $familySet->load('set');

        return $familySet;
    }
}

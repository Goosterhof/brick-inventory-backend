<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\Lego\LegoSetData;
use App\Models\Set;

interface LegoDataServiceInterface
{
    public function getSetParts(string $setNum): Set;

    public function fetchSet(string $setNum): LegoSetData;
}

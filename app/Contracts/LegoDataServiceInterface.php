<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\Lego\LegoSetData;
use App\Data\Lego\LegoSetPartData;

interface LegoDataServiceInterface
{
    public function fetchSet(string $setNum): LegoSetData;

    /**
     * @return list<LegoSetPartData>
     */
    public function fetchSetParts(string $setNum): array;
}

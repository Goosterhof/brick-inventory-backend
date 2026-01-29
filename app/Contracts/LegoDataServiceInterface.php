<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\Lego\LegoSetData;
use App\Data\Lego\LegoSetPartData;
use App\Data\Lego\RebrickableUserSetData;

interface LegoDataServiceInterface
{
    public function fetchSet(string $setNum): LegoSetData;

    /**
     * @return list<LegoSetPartData>
     */
    public function fetchSetParts(string $setNum): array;

    /**
     * Fetch all sets from a user's Rebrickable collection.
     *
     * @return list<RebrickableUserSetData>
     */
    public function fetchUserSets(string $userToken): array;
}

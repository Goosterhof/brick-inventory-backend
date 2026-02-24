<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\Lego\LegoSetData;
use App\Data\Lego\LegoSetPartData;
use App\Data\Lego\RebrickableUserSetData;
use App\Exceptions\InvalidApiResponseException;
use App\Exceptions\RebrickableApiException;
use App\Exceptions\SetNotFoundException;
use Generator;

interface LegoDataServiceInterface
{
    /**
     * Fetch a LEGO set by its set number.
     *
     * @throws SetNotFoundException
     * @throws RebrickableApiException
     * @throws InvalidApiResponseException
     */
    public function fetchSet(string $setNum): LegoSetData;

    /**
     * Fetch all parts for a LEGO set.
     *
     * @throws RebrickableApiException
     * @throws InvalidApiResponseException
     *
     * @return list<LegoSetPartData>
     */
    public function fetchSetParts(string $setNum): array;

    /**
     * Fetch sets from a user's Rebrickable collection, yielding one page at a time.
     *
     * @throws RebrickableApiException
     * @throws InvalidApiResponseException
     *
     * @return Generator<int, list<RebrickableUserSetData>>
     */
    public function fetchUserSets(string $userToken): Generator;
}

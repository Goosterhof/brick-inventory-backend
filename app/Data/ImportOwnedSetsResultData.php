<?php

declare(strict_types=1);

namespace App\Data;

/**
 * DTO for the result of importing owned sets from Rebrickable.
 */
final readonly class ImportOwnedSetsResultData
{
    public function __construct(
        public int $created,
        public int $updated,
        public int $total,
    ) {}
}

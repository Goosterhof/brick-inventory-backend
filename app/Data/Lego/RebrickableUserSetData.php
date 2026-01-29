<?php

declare(strict_types=1);

namespace App\Data\Lego;

/**
 * DTO for a set in a user's Rebrickable collection.
 */
final readonly class RebrickableUserSetData
{
    public function __construct(
        public LegoSetData $set,
        public int $quantity,
    ) {}
}

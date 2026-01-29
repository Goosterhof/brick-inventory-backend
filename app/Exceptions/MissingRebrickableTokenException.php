<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class MissingRebrickableTokenException extends Exception
{
    public static function forFamily(int $familyId): self
    {
        return new self(sprintf('Family %d does not have a Rebrickable user token configured', $familyId));
    }
}

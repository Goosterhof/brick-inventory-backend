<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when a LEGO part cannot be found in the database.
 */
class PartNotFoundException extends Exception
{
    public static function forPartNum(string $partNum): self
    {
        return new self(sprintf("Part with number '%s' not found", $partNum));
    }
}

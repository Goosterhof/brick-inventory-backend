<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class CannotRemoveSelfException extends Exception
{
    public static function forUser(int $userId): self
    {
        return new self(sprintf('User %d cannot remove themselves from the family', $userId));
    }
}

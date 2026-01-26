<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

/**
 * Base exception for Rebrickable API errors.
 */
class RebrickableApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly ?Response $response = null,
    ) {
        parent::__construct($message);
    }

    public static function fromResponse(Response $response, string $context = ''): self
    {
        $message = $context !== ''
            ? sprintf('%s: HTTP %d', $context, $response->status())
            : sprintf('Rebrickable API error: HTTP %d', $response->status());

        return new self($message, $response->status(), $response);
    }
}

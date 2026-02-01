<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

/**
 * Exception thrown when the Brickognize API returns an error or unexpected response.
 */
class BrickognizeApiException extends Exception
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
            : sprintf('Brickognize API error: HTTP %d', $response->status());

        return new self($message, $response->status(), $response);
    }

    public static function invalidResponse(string $context = ''): self
    {
        $message = $context !== ''
            ? sprintf('%s: Invalid response structure', $context)
            : 'Brickognize API returned an invalid response structure';

        return new self($message);
    }

    public static function noItemsFound(): self
    {
        return new self('No LEGO parts could be identified in the image');
    }
}

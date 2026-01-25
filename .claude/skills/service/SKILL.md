---
name: service
description: Create Service classes for external API connections following project conventions
argument-hint: <ServiceName>
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(composer lint)
---

# Service Skill

You are helping create Service classes for external API connections in a Laravel project following strict conventions.

## Purpose

Service classes in this codebase are specifically for **external API communication**. They should:
- Handle HTTP requests to external APIs
- Use custom exceptions for error handling
- Validate API responses
- Delegate persistence to Action classes (separation of concerns)

## Arguments

Parse `$ARGUMENTS` to get the service name (e.g., `Rebrickable`, `Stripe`, `GitHub`).

The name should NOT include the `Service` suffix - it will be added automatically.

## File Structure

Services create these files:
- `app/Services/{Name}Service.php` - The service class
- `app/Exceptions/{Name}ApiException.php` - Base API exception
- `app/Exceptions/{Resource}NotFoundException.php` - 404 exception (optional)
- `app/Exceptions/Invalid{Name}ResponseException.php` - Response validation exception
- `app/Contracts/{Name}ServiceInterface.php` - Interface (if needed for abstraction)

## Service Template

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\{Name}ApiException;
use App\Exceptions\Invalid{Name}ResponseException;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final readonly class {Name}Service
{
    private const array REQUIRED_FIELDS = ['id', 'name']; // Customize per endpoint

    public function __construct(
        #[Config('services.{name}.key', '')] private string $apiKey,
        #[Config('services.{name}.base_url', 'https://api.example.com')] private string $baseUrl,
    ) {}

    /**
     * @return array{id: int, name: string}
     *
     * @throws {Name}ApiException
     * @throws Invalid{Name}ResponseException
     */
    public function fetchResource(string $identifier): array
    {
        $response = $this->httpClient()->get(sprintf('/resources/%s', $identifier));

        $this->handleErrorResponse($response, $identifier);

        $data = $response->json();

        $this->validateResponse($data, self::REQUIRED_FIELDS, "Fetching resource '{$identifier}'");

        /** @var array{id: int, name: string} $data */
        return $data;
    }

    private function httpClient(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders(['Authorization' => 'Bearer ' . $this->apiKey])
            ->timeout(30)
            ->retry(3, 100, throw: false);
    }

    /**
     * @throws {Name}ApiException
     */
    private function handleErrorResponse(Response $response, string $context): void
    {
        if ($response->successful()) {
            return;
        }

        throw {Name}ApiException::fromResponse($response, $context);
    }

    /**
     * @param list<string> $requiredFields
     *
     * @throws Invalid{Name}ResponseException
     */
    private function validateResponse(mixed $data, array $requiredFields, string $context): void
    {
        if (!is_array($data)) {
            throw Invalid{Name}ResponseException::invalidStructure($context, 'Expected array response');
        }

        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                $missingFields[] = $field;
            }
        }

        if ($missingFields !== []) {
            throw Invalid{Name}ResponseException::missingFields($missingFields, $context);
        }
    }
}
```

## Exception Templates

### Base API Exception

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

/**
 * Base exception for {Name} API errors.
 */
class {Name}ApiException extends Exception
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
            : sprintf('{Name} API error: HTTP %d', $response->status());

        return new self($message, $response->status(), $response);
    }
}
```

### Resource Not Found Exception

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Exception thrown when a resource is not found in the {Name} API.
 */
class {Resource}NotFoundException extends {Name}ApiException
{
    public static function forIdentifier(string $identifier): self
    {
        return new self(
            message: sprintf("{Resource} '%s' not found", $identifier),
            statusCode: 404,
        );
    }
}
```

### Invalid Response Exception

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Exception thrown when the {Name} API returns an unexpected response structure.
 */
class Invalid{Name}ResponseException extends {Name}ApiException
{
    /**
     * @param list<string> $missingFields
     */
    public static function missingFields(array $missingFields, string $context = ''): self
    {
        $fieldsStr = implode(', ', $missingFields);
        $message = $context !== ''
            ? sprintf('%s: Missing required fields: %s', $context, $fieldsStr)
            : sprintf('Invalid API response: Missing required fields: %s', $fieldsStr);

        return new self($message);
    }

    public static function invalidStructure(string $context, string $details = ''): self
    {
        $message = $details !== ''
            ? sprintf('%s: Invalid response structure - %s', $context, $details)
            : sprintf('%s: Invalid response structure', $context);

        return new self($message);
    }
}
```

## Configuration

Add to `config/services.php`:

```php
'{name}' => [
    'key' => env('{NAME}_API_KEY'),
    'base_url' => env('{NAME}_BASE_URL', 'https://api.example.com'),
],
```

## Key Conventions

### 1. Use `#[Config]` Attribute
Inject configuration via Laravel's `#[Config]` attribute with default values:

```php
#[Config('services.stripe.key', '')] private string $apiKey,
#[Config('services.stripe.base_url', 'https://api.stripe.com')] private string $baseUrl,
```

### 2. HTTP Client Best Practices

```php
private function httpClient(): PendingRequest
{
    return Http::baseUrl($this->baseUrl)          // Enables relative URLs
        ->withHeaders(['Authorization' => '...'])  // Auth header
        ->timeout(30)                              // 30 second timeout
        ->retry(3, 100, throw: false);            // 3 retries, 100ms delay, don't throw
}
```

**Important:** Use `throw: false` in `retry()` to allow custom exception handling instead of Laravel's `RequestException`.

### 3. Separation of Concerns

Services should ONLY handle HTTP communication. Delegate persistence to Action classes:

```php
// Good - Service fetches, Action persists
public function __construct(
    private UpsertResourceAction $upsertAction,
) {}

public function syncResource(string $id): Resource
{
    $data = $this->fetchResource($id);  // HTTP only
    return $this->upsertAction->execute($data);  // Persistence delegated
}
```

### 4. PHPDoc Type Annotations

Use detailed array shape annotations for API responses:

```php
/**
 * @return array{id: int, name: string, email: string|null, created_at: string}
 */
public function fetchUser(int $id): array

/**
 * @return list<array{id: int, title: string, completed: bool}>
 */
public function fetchTasks(): array
```

### 5. Pagination Handling

For paginated APIs, accumulate results:

```php
/**
 * @return list<array{id: int, name: string}>
 */
public function fetchAllResources(): array
{
    $resources = [];
    $url = '/resources';

    do {
        $response = $this->httpClient()->get($url);

        if ($response->failed()) {
            throw {Name}ApiException::fromResponse($response, 'Fetching resources');
        }

        $data = $response->json();
        $this->validatePaginatedResponse($data);

        $resources = array_merge($resources, $data['results']);
        $url = $data['next'];
    } while ($url !== null);

    return $resources;
}
```

### 6. Custom Error Handling

Map HTTP status codes to specific exceptions:

```php
private function handleErrorResponse(Response $response, string $identifier): void
{
    if ($response->successful()) {
        return;
    }

    if ($response->status() === 404) {
        throw ResourceNotFoundException::forIdentifier($identifier);
    }

    if ($response->status() === 401) {
        throw {Name}ApiException::fromResponse($response, 'Invalid API key');
    }

    throw {Name}ApiException::fromResponse($response, "Failed to fetch '{$identifier}'");
}
```

## Workflow

1. Parse the service name from `$ARGUMENTS`
2. Create the exceptions directory if needed: `app/Exceptions/`
3. Generate the base API exception
4. Generate the invalid response exception
5. Optionally generate resource-specific not found exceptions
6. Generate the service class
7. Add configuration to `config/services.php`
8. Run `composer lint` to format code
9. Invoke `/unit-test {Name}Service` to generate tests

## Testing Services

Services should be tested with `Http::fake()`:

```php
use Illuminate\Support\Facades\Http;

it('should fetch resource from API', function (): void {
    Http::fake([
        'https://api.example.com/resources/123' => Http::response([
            'id' => 123,
            'name' => 'Test Resource',
        ]),
    ]);

    $service = new ExampleService('api-key', 'https://api.example.com');
    $result = $service->fetchResource('123');

    expect($result['id'])->toBe(123);
});

it('should throw NotFoundException for 404 response', function (): void {
    Http::fake([
        'https://api.example.com/resources/999' => Http::response([], 404),
    ]);

    $service = new ExampleService('api-key', 'https://api.example.com');

    expect(fn () => $service->fetchResource('999'))
        ->toThrow(ResourceNotFoundException::class);
});

it('should throw InvalidResponseException for missing fields', function (): void {
    Http::fake([
        'https://api.example.com/resources/123' => Http::response([
            'id' => 123,
            // missing 'name'
        ]),
    ]);

    $service = new ExampleService('api-key', 'https://api.example.com');

    expect(fn () => $service->fetchResource('123'))
        ->toThrow(InvalidExampleResponseException::class);
});
```

## Example Usage

User runs: `/service Stripe`

Creates:
- `app/Exceptions/StripeApiException.php`
- `app/Exceptions/InvalidStripeResponseException.php`
- `app/Services/StripeService.php`
- Updates `config/services.php` with Stripe config

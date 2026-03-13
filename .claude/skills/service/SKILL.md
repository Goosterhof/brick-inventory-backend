---
name: service
description: Create Service classes for external API connections following project conventions
argument-hint: <ServiceName>
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(composer lint)
---

# Service Skill

Create Service classes for external APIs. Parse `$ARGUMENTS` for the name (without `Service` suffix).

## CRITICAL: Services = HTTP Only

Services must NEVER include:
- Database queries or persistence
- Action class dependencies
- Business logic or orchestration

If a method needs an Action or Model for persistence, that logic belongs in an Action.

## What Gets Created

1. `app/Services/{Name}Service.php`
2. `app/Exceptions/{Name}ApiException.php` — base, with `fromResponse()` static constructor
3. `app/Exceptions/Invalid{Name}ResponseException.php` — missing fields, invalid structure
4. `app/Exceptions/{Resource}NotFoundException.php` — 404 (optional)
5. Config entry in `config/services.php`

Read existing services and exceptions for the template patterns.

## Key Conventions

### Config Injection
```php
#[Config('services.{name}.key', '')] private string $apiKey,
#[Config('services.{name}.base_url', 'https://api.example.com')] private string $baseUrl,
```

### HTTP Client
```php
private function httpClient(): PendingRequest
{
    return Http::baseUrl($this->baseUrl)
        ->withHeaders(['Authorization' => 'Bearer ' . $this->apiKey])
        ->timeout(30)
        ->retry(3, 100, throw: false);  // throw: false → custom exception handling
}
```

### Pagination
For paginated APIs, accumulate results in a `do/while` loop following `$data['next']`.

### Error Handling
Map HTTP status codes to specific exceptions (404 → NotFoundException, 401 → API key error, etc.).

## After Creation

1. Run `composer lint`
2. Invoke `/unit-test {Name}Service`

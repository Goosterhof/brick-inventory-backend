# ADR-0008: Explicit Routes, Not apiResource

**Status:** Accepted

## Context

Laravel provides `Route::apiResource()` to register standard CRUD routes in one call. This project uses per-route authorization via `->can()` middleware.

## Decision

Define every route explicitly:

```php
Route::get('storage-options', [StorageOptionController::class, 'index'])
    ->can('viewAny', StorageOption::class);
Route::post('storage-options', [StorageOptionController::class, 'store'])
    ->can('create', StorageOption::class);
```

## Alternatives Considered

- **`Route::apiResource()`** — convenient but makes it hard to apply per-route `->can()` middleware, hides the actual API surface, and doesn't support custom method names cleanly.

## Consequences

- Every endpoint is visible at a glance in `routes/api.php`
- Authorization middleware is explicit per route
- Route naming: kebab-case plural paths, snake_case singular parameters

## Enforced By

- `tests/Architecture/RoutingArchitectureTest.php` — every authorized route must have `can:` middleware

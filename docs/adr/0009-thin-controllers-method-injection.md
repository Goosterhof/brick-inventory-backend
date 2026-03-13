# ADR-0009: Thin Controllers with Method Injection

**Status:** Accepted

## Context

Controllers can receive dependencies via constructor injection or method injection. With the Action pattern, controllers are thin dispatchers.

## Decision

Controllers must **not have constructors**. All dependencies are injected per method:

```php
public function store(
    StoreStorageOptionRequest $request,
    CreateStorageOptionAction $createAction,
    GetStorageOptionAction $getAction,
): JsonResponse {
    $storageOption = $createAction->execute($request);
    $storageOption = $getAction->execute($storageOption);
    return StorageOptionResourceData::from($storageOption)->toResponse();
}
```

Additional rules:
- No try-catch blocks — exceptions handled globally in `bootstrap/app.php`
- Return `JsonResponse` or `array`, never ResourceData directly
- No `Gate` injection, no `->authorize()` calls

## Alternatives Considered

- **Constructor injection** — front-loads all dependencies even for unused methods. Encourages fat controllers.

## Consequences

- Each method declares exactly what it needs
- Controllers stay thin — typically 3-5 lines per method
- Exception handling is consistent across all endpoints

## Enforced By

- `tests/Architecture/ControllerArchitectureTest.php` — no constructors, no try-catch, return types, no Gate injection

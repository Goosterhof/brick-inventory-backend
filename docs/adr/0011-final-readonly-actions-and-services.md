# Decision: Final Readonly Actions and Services

**Date**: 2026-03-22
**Feature**: Immutability guarantees for business logic and external integrations
**Status**: accepted
**Transferability**: universal

## Context

Actions (business logic) and Services (external HTTP adapters) are the two layers where the most critical operations happen. In a Laravel codebase, nothing prevents subclassing a service to override behavior, or mutating injected dependencies mid-execution. Both are sources of subtle bugs:

- A subclassed Action can silently change business rules without updating tests
- A mutable service could have its HTTP client swapped after construction
- PHP's `readonly` properties are only enforced if the class is declared `readonly`

The question: should we enforce immutability at the language level, or trust convention?

## Options Considered

| Option | Pros | Cons | Why eliminated / Why chosen |
|--------|------|------|-----------------------------|
| **`final readonly` on all Actions and Services** | Compiler-enforced immutability, no subclassing, clear intent | Cannot mock with Mockery without `BypassFinals`; slightly unconventional in Laravel | **Chosen** — the safety guarantee outweighs the testing workaround |
| **`final` only (no `readonly`)** | Prevents subclassing | Doesn't prevent property mutation after construction | Eliminated — half the guarantee |
| **`readonly` only (no `final`)** | Immutable properties | Subclassing can override methods and change behavior | Eliminated — half the guarantee |
| **Convention only** | No restrictions on testing | No enforcement; relies on code review | Eliminated — the whole point is automation |

## Decision

All Action and Service classes must be declared `final readonly`. This is enforced by architecture tests that scan for the literal string `final readonly class` in every file under `app/Actions/` and `app/Services/`.

For testing, `BypassFinals` is enabled in `tests/Pest.php` with a whitelist limited to `app/Actions/*`, allowing Mockery to mock Action dependencies in unit tests while the production constraint remains enforced.

```php
// Every Action
final readonly class CreateStorageOptionAction
{
    public function __construct(
        private StorageOption $storageOption,
    ) {}

    public function execute(StorageOptionData $data, User $user): StorageOption { ... }
}

// Every Service
final readonly class RebrickableService implements LegoDataServiceInterface
{
    public function __construct(
        private Factory $httpFactory,
        #[Config('services.rebrickable.key')] private string $apiKey,
    ) {}
}
```

## Consequences

- No Action or Service can be subclassed — behavior changes require modifying the original or creating a new class
- All injected dependencies are immutable after construction — no mid-request state changes
- Mockery requires `BypassFinals` to mock these classes in tests (already configured)
- Developers must think in composition, not inheritance — which is the goal

## Enforcement

| What | Mechanism | Scope |
|------|-----------|-------|
| Actions are `final readonly` | `ActionArchitectureTest` | `app/Actions/` |
| Services are `final readonly` | `ServiceArchitectureTest` | `app/Services/` |
| BypassFinals configured for test mocking | `tests/Pest.php` | Test suite |

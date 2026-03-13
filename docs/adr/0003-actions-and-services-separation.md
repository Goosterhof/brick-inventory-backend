# ADR-0003: Actions for Business Logic, Services for HTTP Only

**Status:** Accepted

## Context

Business logic, external API calls, and database operations need clear ownership. Laravel doesn't prescribe where orchestration lives.

## Decision

Split into two layers with strict boundaries:

**Actions** — business logic and orchestration:
- Database operations (via injected models with `newQuery()`/`newInstance()`)
- Calling services for external data
- Calling other actions for sub-operations
- Single `execute()` method, `final readonly`

**Services** — external HTTP only:
- HTTP requests/responses
- Response parsing and validation
- Custom exception handling for API errors
- No business logic, no database, no Action dependencies

```
Controller
  └─ GetSetPartsAction (orchestration)
       ├─ RebrickableService.fetchSet() → HTTP only
       ├─ UpsertSetAction → DB operation
       └─ StoreSetPartsAction → DB operation
```

## Alternatives Considered

- **Fat controllers** — quickly becomes untestable and violates SRP.
- **Services doing everything** — blurs the line between external communication and business logic. Tested in a previous iteration and caused circular dependencies.
- **Repository pattern** — adds abstraction over Eloquent with little benefit at this scale.

## Consequences

- Actions can depend on other Actions (delegation over duplication)
- Services are independently testable with `Http::fake()`
- Deptrac enforces: Services cannot depend on Actions or Models

## Enforced By

- `tests/Architecture/ActionArchitectureTest.php` — `final readonly`, single `execute()`, no facades, no Request dependencies
- `tests/Architecture/ServiceArchitectureTest.php` — `final`, no Actions, no Models
- `deptrac.yaml` — layer dependency rules

# Decision: Actions for Business Logic, Services for HTTP Only

**Date**: 2026-03-22
**Feature**: Separation of business logic from external API communication
**Status**: accepted
**Transferability**: universal

## Context

Business logic, external API calls, and database operations need clear ownership. Laravel doesn't prescribe where orchestration lives — logic often ends up in controllers, models, or ambiguous "service" classes that do everything.

A previous iteration of this codebase put business logic and HTTP calls in the same service classes. This caused circular dependencies (Service A needed Service B's data, but Service B called an Action that needed Service A) and made testing painful — you couldn't test business logic without faking HTTP calls.

## Options Considered

| Option | Pros | Cons | Why eliminated / Why chosen |
|--------|------|------|-----------------------------|
| **Actions (business logic) + Services (HTTP only)** | Clear boundary; Actions testable without HTTP fakes; Services testable without database; composable via delegation | Two class types to learn; more files | **Chosen** — the boundary paid for itself immediately in test clarity |
| **Fat controllers** | Familiar; quick to write | Untestable; violates SRP; logic duplicated across endpoints | Eliminated — doesn't survive the first refactor |
| **Services doing everything** | One abstraction to learn | Blurs external communication and business logic; tested in previous iteration and caused circular dependencies | Eliminated — empirically failed |
| **Repository pattern** | Abstracts database access | Adds indirection over Eloquent with little benefit at this scale; doesn't address the HTTP boundary | Eliminated — solves a different problem |

## Decision

Split into two layers with strict boundaries enforced by Deptrac and architecture tests:

**Actions** — business logic and orchestration:
- Database operations (via injected models with `newQuery()`/`newInstance()`)
- Calling Services for external data
- Calling other Actions for sub-operations
- Single `execute()` method, `final readonly`
- No facades, no Request objects, no try-catch

**Services** — external HTTP only:
- HTTP requests/responses via injected `Http\Client\Factory`
- Response parsing and validation
- Custom exception handling for API errors
- No business logic, no database access, no Model dependencies, no Action dependencies

```
Controller
  └─ GetSetPartsAction (orchestration)
       ├─ RebrickableService.fetchSet() → HTTP only
       ├─ UpsertSetAction → DB operation
       └─ StoreSetPartsAction → DB operation
```

## Consequences

- Actions can depend on other Actions (delegation over duplication)
- Services are independently testable with `Http::fake()` — no database needed
- Actions are testable with mocked Services — no HTTP fakes needed
- Deptrac enforces: Services cannot depend on Actions, Models, or other Services
- Each Service must implement a Contract interface — enabling test doubles

## Enforcement

| What | Mechanism | Scope |
|------|-----------|-------|
| Actions are `final readonly` with single `execute()` | `ActionArchitectureTest` | `app/Actions/` |
| Actions: no facades, no Request dependencies | `ActionArchitectureTest` | `app/Actions/` |
| Actions: explicit transactions (no arrow functions) | `ActionArchitectureTest` | `app/Actions/` |
| Services are `final readonly` implementing a Contract | `ServiceArchitectureTest` | `app/Services/` |
| Services: no Models, no Actions, no other Services | `ServiceArchitectureTest` | `app/Services/` |
| Layer dependency rules | `deptrac.yaml` | All application layers |

## Resolved Questions

### Can an Action call another Action?

**Resolved 2026-03-22.** Yes — Actions can depend on other Actions for sub-operations. This enables delegation over duplication. Deptrac explicitly allows `Action → Action` dependencies. The constraint is that this doesn't create circular dependencies (which Deptrac would catch at the layer level, though not at the class level).

### Why no try-catch in Actions?

**Resolved 2026-03-22.** Exceptions bubble to the global handler in `bootstrap/app.php`, which maps typed exceptions to HTTP responses. This keeps error handling consistent and prevents Actions from silently swallowing failures.

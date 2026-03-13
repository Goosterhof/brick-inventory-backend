# ADR-0006: DTOFormRequest and Custom ResourceData

**Status:** Accepted

## Context

Data crosses two boundaries: request → action (input) and model → response (output). Laravel provides FormRequest for validation and ApiResource for responses. Both have limitations for type safety.

## Decision

### Input: DTOFormRequest Pattern

FormRequests extend `DTOFormRequest` (not Laravel's `FormRequest`) and act as both validator and DTO:
- `final readonly` class with typed constructor properties
- Implements a contract interface (e.g., `CreateStorageOptionInterface`)
- `toDTO()` maps validated request data to typed properties
- Actions accept the interface, not the concrete request

This means the same interface can be implemented by a FormRequest (HTTP context) or an anonymous class (test context).

### Output: Custom ResourceData

Response DTOs extend a custom `ResourceData` base class instead of Laravel's `ApiResource`:
- `final readonly` with snake_case properties
- `from(Model)` static factory, `collection()` for lists
- `requiredRelations()` for N+1 prevention
- Implements `JsonSerializable` + `Responsable`
- Controllers call `->toResponse()`, never return ResourceData directly

## Alternatives Considered

- **Standard FormRequest + array** — loses type safety at the action boundary.
- **Laravel ApiResource** — wraps responses in `data` key, less explicit about properties, lazy loading risks.
- **Spatie Data** — evaluated, but custom ResourceData is simpler and already in place.

## Consequences

- Contract interfaces in `app/Contracts/{Domain}/` for every create/update operation
- Unit tests use anonymous classes implementing the interface
- ResourceData classes self-document their relationship dependencies via `requiredRelations()`
- No `data` wrapper in API responses

## Enforced By

- `tests/Architecture/RequestArchitectureTest.php` — `final`, extends `FormRequest`
- `tests/Architecture/ResourceDataArchitectureTest.php` — `final readonly`, `from()` method, `requiredRelations()` when accessing relations
- `tests/Architecture/ControllerArchitectureTest.php` — controllers must not return ResourceData directly

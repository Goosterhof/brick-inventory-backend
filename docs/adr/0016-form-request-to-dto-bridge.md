# Decision: FormRequest-to-DTO Bridge Pattern

**Date**: 2026-03-22
**Feature**: Type-safe input handling from HTTP boundary to business logic
**Status**: accepted
**Transferability**: universal

## Context

ADR-0006 established that FormRequests handle validation and ResourceData handles output shaping. But a gap remained: how does validated input travel from the FormRequest to the Action? Laravel's default is to pass the `Request` object (or call `$request->validated()`), which leaks HTTP concerns into business logic and provides untyped arrays.

Actions accept typed parameters and DTOs (ADR-0003). FormRequests validate HTTP input. Something must bridge the two — converting a validated request into a typed, immutable DTO that Actions can trust.

## Options Considered

| Option | Pros | Cons | Why eliminated / Why chosen |
|--------|------|------|-----------------------------|
| **`toDto()` method on FormRequest** | Bridge is co-located with validation; controller stays thin; DTO construction uses `$this->safe()` for safety | FormRequest has two responsibilities (validation + DTO construction) | **Chosen** — the "two responsibilities" concern is minor; the bridge is trivially simple and directly maps validated fields |
| **Separate mapper/factory class** | Pure SRP; FormRequest only validates | Extra class per request; indirection for a simple mapping; controller must orchestrate | Eliminated — over-engineering for a direct field-to-property mapping |
| **Pass `$request->validated()` array to Action** | No extra classes | Untyped; Action must validate array structure; loses type safety | Eliminated — defeats the purpose of typed parameters |
| **Pass FormRequest directly to Action** | Simple | Violates ADR-0003 (Actions must not depend on Request objects); couples business logic to HTTP | Eliminated — architectural violation |

## Decision

Every FormRequest that feeds an Action must declare a `toDto()` method that returns a `final readonly` DTO. The controller calls `$request->toDto()` and passes the result to the Action.

```php
// FormRequest — validates and bridges
final class StoreFamilySetRequest extends FormRequest
{
    private const string SET_NUM = 'set_num';
    private const string QUANTITY = 'quantity';

    public function rules(): array
    {
        return [
            self::SET_NUM => ['required', 'string', 'max:255'],
            self::QUANTITY => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function toDto(): CreateFamilySetData
    {
        return new CreateFamilySetData(
            setNum: $this->safe()->string(self::SET_NUM)->toString(),
            quantity: $this->isNotFilled(self::QUANTITY)
                ? 1
                : $this->safe()->integer(self::QUANTITY),
        );
    }
}

// DTO — immutable, typed
final readonly class CreateFamilySetData
{
    public function __construct(
        public string $setNum,
        public int $quantity,
    ) {}
}

// Controller — thin bridge
public function store(StoreFamilySetRequest $request, CreateFamilySetAction $action): JsonResponse
{
    return $action->execute($request->toDto(), $request->user());
}
```

**Conventions within the pattern:**
- Field name constants are `private const` — internal to the FormRequest, not part of its public API
- `$this->safe()` is always used (never raw `$this->input()`) — only validated data enters the DTO
- Default values for optional fields are handled in `toDto()`, not in the DTO constructor
- Type coercion (enum parsing, date parsing) happens in `toDto()` — the DTO receives final types

## Consequences

- Actions never see HTTP request objects — complete decoupling from the transport layer
- DTOs are immutable and typed — Actions can trust their input without re-validation
- Each FormRequest has a clear, testable contract: rules + toDto
- Controllers reduce to one-liners: `$action->execute($request->toDto(), ...)`
- Adding a field means updating three places: rules, toDto, and the DTO class — but this is a feature (explicit surface area)

## Enforcement

| What | Mechanism | Scope |
|------|-----------|-------|
| FormRequests are `final` | `RequestArchitectureTest` | `app/Http/Requests/` |
| No public constants on FormRequests | `RequestArchitectureTest` | `app/Http/Requests/` |
| Actions don't accept Request objects | `ActionArchitectureTest` | `app/Actions/` |
| DTOs are `final readonly` | Convention (DTO classes follow pattern) | `app/DataTransferObjects/` |

## Resolved Questions

### Why private constants instead of inline strings?

**Resolved 2026-03-22.** Constants prevent typos between `rules()` and `toDto()` — a misspelled field name is a compile-time error, not a runtime null. They're `private` because external code should never reference a FormRequest's field names.

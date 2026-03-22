# Decision: DTOFormRequest and Custom ResourceData

**Date**: 2026-03-22
**Feature**: Type-safe data crossing the HTTP boundary in both directions
**Status**: accepted
**Transferability**: universal

## Context

Data crosses two boundaries: request → action (input) and model → response (output). Laravel provides FormRequest for validation and ApiResource for responses. Both have limitations:

- FormRequest validates but returns untyped arrays via `$request->validated()`
- ApiResource wraps responses in a `data` key, has implicit lazy loading risks, and doesn't self-document relationship dependencies

The forces:
- Actions must receive typed input (ADR-0003) — no raw arrays or Request objects
- Responses must be predictable — no surprise `data` wrappers, no N+1 queries from lazy loading
- The pattern must be enforceable by architecture tests

## Options Considered

| Option | Pros | Cons | Why eliminated / Why chosen |
|--------|------|------|-----------------------------|
| **FormRequest with `toDto()` + custom ResourceData** | Type-safe both directions; self-documenting relationships; no `data` wrapper; testable | Custom base class for output; two concepts to learn | **Chosen** — type safety at both boundaries is non-negotiable |
| **Standard FormRequest + `$request->validated()` array** | Familiar; zero setup | Loses type safety at the action boundary; Actions receive untyped arrays | Eliminated — defeats the purpose of typed Actions |
| **Laravel ApiResource** | Built-in; well-documented | Wraps in `data` key; lazy loading risks; less explicit about properties; hard to enforce eager loading | Eliminated — too much implicit behavior |
| **Spatie Laravel Data** | Full-featured; handles both input and output | Evaluated — but custom ResourceData is simpler, already in place, and does exactly what's needed without the package overhead | Eliminated — over-featured for the requirements |

## Decision

### Input: FormRequest with `toDto()` Bridge

FormRequests validate HTTP input and produce typed DTOs via a `toDto()` method. Controllers call `$request->toDto()` and pass the result to Actions. See ADR-0016 for the detailed bridge pattern.

```php
final class StoreFamilySetRequest extends FormRequest
{
    public function rules(): array { /* validation rules */ }

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
```

### Output: Custom ResourceData

Response DTOs extend a custom `ResourceData` base class (not Laravel's ApiResource):
- `final readonly` concrete classes with snake_case properties
- Static `from(Model)` factory method — constructs from Manifest data
- `collection()` for lists — handles eager loading automatically
- `EAGER_LOAD` constant when nesting related data — prevents N+1 queries
- Implements `JsonSerializable` + `Responsable` — controllers call `->toResponse()`

```php
final readonly class StorageOptionResourceData extends ResourceData
{
    public const array EAGER_LOAD = ['children', 'storageOptionParts'];

    public static function from(StorageOption $storageOption): self
    {
        $storageOption->loadMissing(self::EAGER_LOAD);
        return new self(
            id: $storageOption->id,
            name: $storageOption->name,
            // ...
        );
    }
}
```

## Consequences

- Type safety at both boundaries — Actions receive DTOs, clients receive structured responses
- No `data` wrapper in API responses — ResourceData serializes directly
- `EAGER_LOAD` constants self-document relationship dependencies — architecture tests enforce their presence when nested ResourceData is used
- Controllers call `->toResponse()` or `->toResponseWithStatus()` — never return ResourceData directly
- Adding a new endpoint means creating a FormRequest (with DTO), a ResourceData, and wiring them through a thin controller

## Enforcement

| What | Mechanism | Scope |
|------|-----------|-------|
| FormRequests are `final` | `RequestArchitectureTest` | `app/Http/Requests/` |
| No public constants on FormRequests | `RequestArchitectureTest` | `app/Http/Requests/` |
| ResourceData concrete classes are `final readonly` | `ResourceDataArchitectureTest` | `app/Http/Resources/` |
| ResourceData has `from()` method | `ResourceDataArchitectureTest` | `app/Http/Resources/` |
| `EAGER_LOAD` required when nesting ResourceData | `ResourceDataArchitectureTest` | `app/Http/Resources/` |
| Controllers don't return ResourceData directly | `ControllerArchitectureTest` | `app/Http/Controllers/` |

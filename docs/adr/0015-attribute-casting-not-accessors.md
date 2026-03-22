# Decision: Attribute Casting via casts(), Not Accessors/Mutators

**Date**: 2026-03-22
**Feature**: Consistent attribute transformation across models
**Status**: accepted
**Transferability**: project-specific

## Context

Laravel offers three mechanisms for attribute transformation: the `casts()` method, legacy `get*Attribute()`/`set*Attribute()` accessors/mutators, and the newer `Attribute::make()` API. All three can coexist on the same model, which creates ambiguity — a developer reading a model can't know where a transformation happens without checking all three locations.

This project needs encrypted tokens, enum casting, date parsing, and boolean coercion. The question: should transformations be centralized, or spread across whichever mechanism fits best per case?

## Options Considered

| Option | Pros | Cons | Why eliminated / Why chosen |
|--------|------|------|-----------------------------|
| **`casts()` method exclusively** | Single location for all transformations; declarative; handles encryption, enums, dates, and primitives | Cannot express computed attributes (derived from multiple columns) | **Chosen** — all current use cases are column-to-type mappings |
| **`Attribute::make()` for everything** | Modern API; supports get/set logic | Verbose for simple casts; mixes declaration with logic; harder to scan | Eliminated — over-engineering for simple type coercion |
| **Mix of `casts()` and accessors** | Use the right tool for each case | No single source of truth; developers must check multiple locations | Eliminated — consistency over flexibility |
| **No casting, transform in Actions** | Models stay pure data | Every Action must know about type transformations; duplicated logic | Eliminated — violates DRY |

## Decision

All attribute transformations use the `casts()` method. No `get*Attribute()`/`set*Attribute()` methods and no `Attribute::make()` calls.

```php
// Family.php — encrypted token
protected function casts(): array
{
    return [
        'rebrickable_user_token' => 'encrypted',
    ];
}

// FamilySet.php — enum, date, integer
protected function casts(): array
{
    return [
        'status' => FamilySetStatus::class,
        'purchase_date' => 'date',
        'quantity' => 'integer',
    ];
}

// User.php — hashed password, datetime
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
```

If a future requirement needs a computed attribute (derived from multiple columns), `Attribute::make()` would be appropriate — but that case hasn't arisen.

## Consequences

- One location to check for all attribute transformations on any model
- Sensitive data (tokens, passwords) is automatically encrypted/hashed via cast declarations
- Enum casting is declarative — no manual `from()` calls scattered through Actions
- Cannot handle computed attributes — would require revisiting if needed
- New developers know exactly where to look: `casts()` and only `casts()`

## Enforcement

| What | Mechanism | Scope |
|------|-----------|-------|
| No accessor/mutator methods on models | Convention (candidate for architecture test) | `app/Models/` |

## Open Questions

- Should an architecture test scan models for `get*Attribute`, `set*Attribute`, or `Attribute::make()` patterns? Currently enforced by convention only. The risk is low (2-person team, clear pattern), but an automated check would close the gap.

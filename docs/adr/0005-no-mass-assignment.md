# Decision: No Mass Assignment

**Date**: 2026-03-22
**Feature**: Explicit property assignment over Laravel's mass assignment
**Status**: accepted
**Transferability**: project-specific

## Context

Laravel provides `$fillable` and `$guarded` arrays for mass assignment protection, enabling `Model::create($data)` and `$model->fill($data)`. This project assigns model properties in Action classes, where the full context of what's being set is always available.

The forces:
- Different Actions may set different subsets of properties on the same model — `$fillable` can't express this
- Mass assignment hides which properties are set — you have to cross-reference the `$fillable` array to know
- The Action pattern (ADR-0003) already centralizes property assignment — mass assignment adds a second indirection layer

## Options Considered

| Option | Pros | Cons | Why eliminated / Why chosen |
|--------|------|------|-----------------------------|
| **Explicit property assignment, no `$fillable`/`$guarded`** | Every assignment visible; auditable; different Actions can set different fields naturally | More verbose; no `Model::create()` convenience | **Chosen** — explicitness is the feature, not the cost |
| **`$fillable` whitelist** | Convenient; standard Laravel | Hides which properties are set per operation; accidentally exposing a field is easy; one list for all contexts | Eliminated — convenience isn't worth the opacity |
| **`$guarded` blacklist** | Even more convenient | Worse than `$fillable` — everything is assignable except the blacklist; one mistake exposes sensitive fields | Eliminated — inverted security model |
| **`$guarded = []` (unguarded)** | Maximum convenience | Maximum danger — every property is mass-assignable | Eliminated — not even considered seriously |

## Decision

**No `$fillable` or `$guarded` on models.** Assign every property explicitly in Action classes:

```php
$model = $this->model->newInstance();
$model->family_id = $user->family_id;
$model->name = $data->name;
$model->save();
```

**Exception:** `User` model keeps `protected $guarded = ['password']` for security — Laravel's auth scaffolding interacts with User in ways that benefit from this guard. The architecture test explicitly skips User for both `$fillable` and `$guarded` checks.

## Consequences

- Every property assignment is visible and auditable in the Action that performs it
- No `Model::create()` or `$model->fill()` calls — these bypass explicit assignment
- More explicit, slightly more verbose — but the Action is already the place where this happens
- New developers must learn the pattern: `newInstance()` → assign properties → `save()`

## Enforcement

| What | Mechanism | Scope |
|------|-----------|-------|
| No `$fillable` on models (except User) | `ModelArchitectureTest` | `app/Models/` |
| No `$guarded` on models (except User) | `ModelArchitectureTest` | `app/Models/` |
| All models have `@property` PHPDoc annotations | `ModelArchitectureTest` | `app/Models/` |

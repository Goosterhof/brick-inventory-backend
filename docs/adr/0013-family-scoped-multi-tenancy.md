# Decision: Family-Scoped Multi-Tenancy via Interface and Middleware

**Date**: 2026-03-22
**Feature**: Tenant isolation for family-owned resources
**Status**: accepted
**Transferability**: universal

## Context

The system is multi-tenant: families own sets, storage locations, and parts. Every request touching a family-owned resource must verify the authenticated user belongs to the owning family. Without a systematic approach, tenant checks scatter across every controller and action, and a single missed check leaks data across families.

The forces:
- Tenant isolation must be automatic, not opt-in per endpoint
- A missed check must be caught structurally, not by code review
- Failed ownership checks should return 404 (not 403) to avoid leaking resource existence
- The mechanism must work with Laravel's route model binding

## Options Considered

| Option | Pros | Cons | Why eliminated / Why chosen |
|--------|------|------|-----------------------------|
| **`BelongsToFamilyInterface` + `EnsureFamilyOwnership` middleware** | Automatic for all route-bound models; 404 masking; interface makes tenancy explicit on the model | Models must implement interface; middleware only catches route-bound models, not manual queries | **Chosen** — covers the dangerous path (HTTP access) automatically |
| **Global scope on models** | Completely automatic, even for manual queries | Magic — hard to debug, easy to accidentally bypass with `withoutGlobalScopes()`, complicates admin tooling | Eliminated — too much hidden behavior |
| **Manual checks in every controller** | Simple, explicit | Guaranteed to be forgotten; no enforcement mechanism | Eliminated — the failure mode this pattern prevents |
| **Policy-only approach** | Uses Laravel's built-in authorization | Requires explicit `authorize()` calls; policies check permissions, not tenancy — conflating concerns | Eliminated — authorization and tenancy are separate questions |

## Decision

Three-layer tenant isolation:

1. **`BelongsToFamilyInterface`** — models that belong to a family implement this contract, exposing `getFamilyId(): int`
2. **`EnsureFamilyOwnership` middleware** — applied to all authenticated routes, iterates over route-bound parameters, and checks any `BelongsToFamilyInterface` implementor against the user's `family_id`
3. **404 response on mismatch** — a family accessing another family's resource sees "Not found", not "Forbidden"

```php
// The contract
interface BelongsToFamilyInterface
{
    public function getFamilyId(): int;
}

// Model implementation
class StorageOption extends Model implements BelongsToFamilyInterface
{
    public function getFamilyId(): int
    {
        return $this->family_id;
    }
}

// Middleware — applied to all tenant routes
foreach ($request->route()?->parameters() ?? [] as $parameter) {
    if ($parameter instanceof BelongsToFamilyInterface
        && $parameter->getFamilyId() !== $user->family_id) {
        return response()->json(['error' => 'Not found'], 404);
    }
}
```

All authenticated, family-scoped routes use the `family.ownership` middleware group in `routes/api.php`.

## Consequences

- Adding a new family-owned model requires implementing `BelongsToFamilyInterface` — the middleware handles the rest
- Route-bound models are automatically checked — no per-controller boilerplate
- Manual queries in Actions are NOT covered by this middleware — Actions must still scope queries by `family_id` (defense in depth)
- 404 masking prevents enumeration attacks but means ownership failures don't appear in authorization logs

## Enforcement

| What | Mechanism | Scope |
|------|-----------|-------|
| Middleware applied to tenant routes | `routes/api.php` group declaration | `routes/api.php` |
| Models with `family_id` implement interface | Convention (candidate for architecture test) | `app/Models/` |
| 404 on ownership mismatch | `EnsureFamilyOwnership` middleware | All `family.ownership` routes |

## Open Questions

- Should an architecture test enforce that every model with a `family_id` column implements `BelongsToFamilyInterface`? Currently this is convention only. A test would close the gap.
- Should Actions that query by `family_id` be tested for correct scoping, or is the middleware sufficient? Current approach: both (belt and suspenders).

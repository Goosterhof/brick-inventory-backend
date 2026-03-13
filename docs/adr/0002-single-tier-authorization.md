# ADR-0002: Single-Tier Authorization

**Status:** Accepted

## Context

A two-tier authorization model (roles + permissions) was evaluated for tenant-scoped resource access. The system isolates data by "families" in a shared database.

## Decision

Use a **single-tier** policy model with three-layer defense in depth:

1. **`EnsureFamilyOwnership` middleware** — tenant isolation. Returns 404 if the resource doesn't belong to the user's family.
2. **Policies** — permission checks. `final readonly`, auto-discovered, enforced via `->can()` on routes.
3. **FormRequest closure rules** — body parameter validation the middleware can't reach (e.g., `parent_id` ownership).

Authorization lives **entirely in the routing layer**. Controllers must not inject `Gate` or call `->authorize()`.

## Alternatives Considered

- **Two-tier (roles + permissions)** — evaluated as overkill for this threat model. Families are small groups, not enterprise orgs with role hierarchies.

## Consequences

- No role tables, no permission tables, no Spatie Permission package
- Every authorized route needs `->can()` middleware explicitly
- Architecture tests enforce no `Gate` injection and no `->authorize()` calls

## Enforced By

- `tests/Architecture/PolicyArchitectureTest.php` — naming, `final readonly`, no Gate injection
- `tests/Architecture/RoutingArchitectureTest.php` — `can:` middleware on all authorized routes

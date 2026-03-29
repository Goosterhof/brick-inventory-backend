# Shipping Order: Harden Job Layer Infrastructure

**Order #:** 2026-03-29-harden-job-layer
**Filed:** 2026-03-29
**Issued By:** Logistics Director (CEO-approved)
**Assigned To:** Head Sorter
**Priority:** Standard

---

## The Shipment

The new Job layer (introduced in `2026-03-28-queue-rebrickable-imports`) shipped clean but has three gaps identified during the Logistics Director's evaluation: a race condition in the concurrency guard, no architecture test enforcing Job conventions, and undocumented conventions for how Jobs interact with Models.

## Scope

### In the Crate

1. **Fix the race condition in `StartImportAction`** — the concurrency check (`whereIn` for pending/in-progress) and the `ImportJob` creation are not atomic. Two concurrent requests can both pass the check before either saves. Close this with a database-level guard (e.g., advisory lock, `INSERT ... WHERE NOT EXISTS`, or a `Cache::lock()` scoped to the family).

2. **Architecture test for the Job layer** — a Pest architecture test (`tests/Architecture/JobArchitectureTest.php` or added to an existing test file) that enforces:
   - All classes in `App\Jobs\` are `final`
   - All classes in `App\Jobs\` implement `ShouldQueue`
   - Any other conventions that emerge during implementation

3. **Document Job-Model interaction convention** — Jobs use method injection via `handle()`, so they can't follow the Action pattern of constructor-injected Models with `newQuery()`. This is fine, but the convention should be explicit. Add a brief note to the relevant ADR or learnings doc so future Jobs follow a consistent pattern.

### Not on This Pallet

- Refactoring `ImportOwnedSetsJob` to use injected Models (the static query pattern is standard for Laravel Jobs — document the convention, don't fight it)
- Adding more Jobs (this order is about hardening the infrastructure, not expanding it)
- Redis/Horizon migration
- Real-time progress updates (WebSocket/broadcast)

## Acceptance Criteria

- [ ] Concurrent `POST /family-sets/import-from-rebrickable` requests from the same family cannot create duplicate pending ImportJobs — verified by a test that simulates the race condition
- [ ] Architecture test exists enforcing `final` and `ShouldQueue` on all Job classes
- [ ] Job-Model interaction pattern is documented (learnings, ADR, or CLAUDE.md — Sorter's choice on location)
- [ ] `composer test` passes — no regressions
- [ ] `composer phpstan` passes at level max
- [ ] `composer deptrac` passes — no boundary violations
- [ ] `composer test:arch` passes — including the new Job architecture test

## References

- Related Order: `2026-03-28-queue-rebrickable-imports` (introduced the Job layer)
- Shift Log: `.claude/records/journals/2026-03-28-queue-rebrickable-imports.md` (concerns section of LD evaluation)
- Deptrac config: `deptrac.yaml` (Job layer definition)

## Notes from the Issuer

**On the race condition:** The shipping order for the import feature suggested "Use a database unique constraint or `Cache::lock()`" for the concurrency guard. The Sorter chose a status check query, which was reasonable but incomplete — it's not atomic. The fix should be minimal: either wrap the check+insert in a database lock, or use an atomic insert pattern. Don't restructure the Action — just close the gap.

**On the architecture test:** Keep it simple. Two assertions (final, ShouldQueue) are enough for now. If Job conventions grow, the test grows with them.

**On documentation:** This is a convention decision, not an ADR. A learnings entry or a note in CLAUDE.md's Warehouse Regulations is sufficient. The key point: "Jobs use static Model queries via `handle()` method injection — this is the standard Laravel pattern and is acceptable despite differing from the Action pattern of constructor-injected Models."

---

**Status:** Open
**Shift Log:** _link to shift log when filed_

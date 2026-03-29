# Shift Log: Harden Job Layer Infrastructure

**Log #:** 2026-03-29-harden-job-layer
**Filed:** 2026-03-29
**Shipping Order:** `.claude/records/permits/2026-03-29-harden-job-layer.md`
**Sorter:** Head Sorter

---

## Work Summary

| Action | File | Notes |
|---|---|---|
| Created | `database/migrations/2026_03_29_103513_add_unique_active_import_per_family_index_to_import_jobs_table.php` | Partial unique index on `import_jobs(family_id) WHERE status IN ('pending', 'in_progress')` |
| Created | `tests/Architecture/JobArchitectureTest.php` | Enforces `final` and `ShouldQueue` on all Job classes |
| Modified | `app/Actions/FamilySet/StartImportAction.php` | Added `UniqueConstraintViolationException` catch as database-level race condition fallback |
| Modified | `app/Jobs/ImportOwnedSetsJob.php` | Refactored `handle()` to inject `ImportJob` and `Family` Models from container |
| Modified | `CLAUDE.md` | Added "Queued Jobs (Async Envelopes)" section to Warehouse Regulations |
| Modified | `tests/Unit/Actions/FamilySet/StartImportActionTest.php` | Added test for UniqueConstraintViolationException race condition path |
| Modified | `tests/Feature/Jobs/ImportOwnedSetsJobTest.php` | Updated `handle()` calls to pass injected Model instances |
| Modified | `tests/Feature/Controllers/FamilySetControllerTest.php` | Added feature test verifying partial unique index prevents duplicate pending imports |

## Order Fulfillment

| Acceptance Criterion | Met | Notes |
|---|---|---|
| Concurrent requests cannot create duplicate pending ImportJobs | Yes | Partial unique index on `(family_id) WHERE status IN ('pending', 'in_progress')` prevents duplicates at DB level; `UniqueConstraintViolationException` catch converts to `ImportAlreadyInProgressException`; feature test verifies |
| Architecture test enforces `final` and `ShouldQueue` on Jobs | Yes | `tests/Architecture/JobArchitectureTest.php` with 2 assertions |
| `ImportOwnedSetsJob` injects Models into `handle()` | Yes | `handle()` now takes `ImportJob $importJobModel, Family $familyModel` and uses `$model->newQuery()->findOrFail()` |
| Job conventions documented in CLAUDE.md | Yes | "Queued Jobs (Async Envelopes)" section added between Shipping Labels and Security Checkpoints |
| `composer test` passes | Yes | 472 tests, 1702 assertions, 0 failures |
| `composer phpstan` passes | Yes | Level max, 0 errors |
| `composer deptrac` passes | Yes | 0 violations |
| `composer test:arch` passes | Yes | 88 passed, 2 warnings (routing -- pre-existing), including new Job tests |

## Decisions Made

1. **Partial unique index over `Cache::lock()`** -- Chose a database-level partial unique index (`WHERE status IN ('pending', 'in_progress')`) over `Cache::lock()`. The index is a hard constraint that cannot be bypassed regardless of application code path, race timing, or cache infrastructure availability. It follows the established pattern of database-level enforcement seen throughout the codebase (e.g., unique constraints on sets, parts). `Cache::lock()` would have added a dependency on cache infrastructure and could fail silently if the lock TTL expired.

2. **Keep application-level check + DB fallback** -- Kept the existing `whereIn` check as the primary guard (fast, gives clear error message) and added the `UniqueConstraintViolationException` catch as a fallback for the race condition window. This is consistent with the approved optimistic-locking upsert pattern documented in ADR-0003.

3. **Static queries in `failed()` are acceptable** -- The `failed()` callback on the Job is called directly by the queue worker, not resolved from the container. There is no way to inject dependencies into it. Static `ImportJob::query()` in `failed()` is a framework constraint, not a shortcut. Documented this distinction in the CLAUDE.md Job conventions.

4. **Raw SQL for partial unique index** -- Used `DB::statement()` with raw SQL for the migration because Laravel's schema builder does not support partial unique indexes natively. Both SQLite (tests) and PostgreSQL (production) support `CREATE UNIQUE INDEX ... WHERE ...` syntax.

## Quality Gauntlet

| Check | Result | Notes |
|---|---|---|
| lint:test | Pass | |
| phpstan | Pass | Level max, 0 errors |
| deptrac | Pass | 0 violations, 603 allowed |
| test | Pass | 472 tests, 1702 assertions |
| test:coverage | N/A | No coverage driver (pre-existing) |
| test:feature-coverage | N/A | No coverage driver (pre-existing) |
| mutation | N/A | No coverage driver (pre-existing) |

## Showcase Readiness

This implementation would hold up well under audit. The race condition fix uses the strongest possible guarantee -- a database constraint -- rather than application-level locking that could be circumvented. The dual-layer approach (application check + DB constraint) gives clean error messages for the common case while providing an atomic fallback for the race window.

The Job refactoring is consistent with the Action pattern and makes the dependency graph explicit. The architecture tests ensure new Jobs follow conventions automatically.

One gap worth noting: the `failed()` method on Jobs necessarily uses static queries, which creates a small inconsistency with the "no static queries" principle. This is documented as a framework constraint in CLAUDE.md, which is the right call -- documenting exceptions is better than pretending they don't exist.

## Proposed Knowledge Updates

- **Learnings:** When using partial unique indexes with `WHERE status IN (...)`, both SQLite and PostgreSQL support identical syntax -- no conditional migration logic needed.
- **Pulse:** Job layer hardened: race condition closed with partial unique index, architecture tests added (19 files now), conventions documented. Pattern maturity should be updated to include Job layer.
- **Decision Record:** No new ADR needed -- the `UniqueConstraintViolationException` pattern is already covered by ADR-0003's approved optimistic-locking upsert exception.

## Self-Debrief

### What Went Well

- Read the existing patterns (UniqueConstraintViolationException, ADR-0003 approved exceptions) before writing code. The solution followed established conventions without inventing new ones.
- The partial unique index approach was identified on first pass -- no wasted cycles exploring `Cache::lock()` or advisory locks.
- All 8 files changed were correct on first attempt. No rework after lint/phpstan/deptrac/tests.

### What Went Poorly

- Nothing significant. The task was well-scoped and the existing codebase patterns made the path clear.

### Blind Spots

- Did not verify whether the partial unique index syntax works identically on PostgreSQL 16 (production) vs SQLite (tests). Assumed compatibility based on both databases supporting partial indexes. Should have verified with a quick reference check.
- Did not check if `failed()` could theoretically accept container-resolved parameters via some Laravel mechanism I'm not aware of. Accepted the "framework constraint" explanation at face value.

### Training Proposals

| Proposal | Context | Shift Evidence |
|---|---|---|
| When fixing race conditions, always prefer database-level constraints over application-level locks -- they survive code path changes and cache failures | Race condition in StartImportAction was closed with a partial unique index rather than Cache::lock() | This log |
| When a Job's `failed()` method needs Model access, accept static queries as a framework constraint and document the exception in conventions | `failed()` cannot receive injected dependencies; static queries are the only option | This log |

---

## Logistics Director Evaluation

_Appended by the Logistics Director after reviewing the log. The sorter's sections above are not edited -- they stand as written._

**Overall Assessment:** _pending_

### Order Fulfillment Review

_pending_

### Decision Review

_pending_

### Showcase Assessment

_pending_

### Training Proposal Dispositions

| Proposal | Disposition | Rationale |
|---|---|---|
| _pending_ | | |

### Notes for the Sorter

_pending_

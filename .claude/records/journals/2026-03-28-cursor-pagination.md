# Shift Log: Cursor Pagination for List Endpoints

**Log #:** 2026-03-28-cursor-pagination
**Filed:** 2026-03-28
**Shipping Order:** `.claude/records/permits/2026-03-28-cursor-pagination.md`
**Sorter:** Head Sorter

---

## Work Summary

Implemented cursor-based pagination on all four list endpoints. Two Actions (`GetFamilySetsAction`, `GetStorageOptionsAction`) had already been partially converted to return `CursorPaginator` but the controllers, tests, and remaining Actions were not updated. Completed the full implementation.

| Action | File | Notes |
|---|---|---|
| Modified | `app/Actions/FamilySet/GetFamilySetsAction.php` | Changed import from `Contracts\CursorPaginator` to concrete `Pagination\CursorPaginator` (needed for `through()` in controller) |
| Modified | `app/Actions/StorageOption/GetStorageOptionsAction.php` | Same import change |
| Modified | `app/Actions/StorageOption/GetStorageOptionPartsAction.php` | Converted from `Collection` return to `CursorPaginator`; added perPage/cursor params, DEFAULT/MAX constants, orderBy id |
| Modified | `app/Actions/Family/GetFamilyPartsAction.php` | Converted from raw array return to `CursorPaginator<stdClass>`; uses `toBase()->cursorPaginate()` for join query; ordered by `storage_option_parts.id`; removed manual `map()` transformation |
| Modified | `app/Http/Controllers/FamilySetController.php` | Index now injects `Request`, passes pagination params to action, uses `through()` to transform items via ResourceData |
| Modified | `app/Http/Controllers/StorageOptionController.php` | Both `index` and `parts` methods updated same way |
| Modified | `app/Http/Controllers/FamilyController.php` | Parts method now passes pagination params, returns paginator directly (items are already stdClass) |
| Modified | `tests/Unit/Actions/FamilySet/GetFamilySetsActionTest.php` | Rewrote to mock `cursorPaginate` instead of `latest/get`; added perPage cap and default tests |
| Modified | `tests/Unit/Actions/StorageOption/GetStorageOptionsActionTest.php` | Rewrote to mock `cursorPaginate` instead of `get`; added perPage cap test |
| Modified | `tests/Unit/Actions/StorageOption/GetStorageOptionPartsActionTest.php` | Rewrote for new pagination signature; added perPage cap and default tests |
| Modified | `tests/Unit/Actions/Family/GetFamilyPartsActionTest.php` | Rewrote to mock `toBase()->cursorPaginate()` chain; added perPage cap and default tests |
| Modified | `tests/Feature/Controllers/FamilySetControllerTest.php` | Updated response assertions for `data` envelope; added pagination tests (default, custom, cap, cursor navigation) |
| Modified | `tests/Feature/Controllers/StorageOptionControllerTest.php` | Same updates for index and parts endpoints |
| Modified | `tests/Feature/Controllers/FamilyControllerTest.php` | Same updates for parts endpoint |

## Order Fulfillment

| Acceptance Criterion | Met | Notes |
|---|---|---|
| All four list endpoints return paginated responses with cursor metadata | Yes | All return `{data, path, per_page, next_cursor, next_page_url, prev_cursor, prev_page_url}` |
| Default page size is 25; per_page query parameter accepted up to 100 | Yes | DEFAULT_PER_PAGE=25, MAX_PER_PAGE=100, enforced via `min()` |
| Requests without pagination params return first page (not full collection) | Yes | `cursorPaginate()` defaults to first page |
| `composer test` passes | Yes | 469 tests, 1715 assertions, 0 failures |
| `composer phpstan` passes at level max | Yes | 0 errors |
| `composer deptrac` passes | Yes | 0 violations |
| Feature tests verify pagination behavior on each endpoint | Yes | 4 new test groups covering default, cap, custom per_page, cursor navigation |
| Architecture tests still pass | Yes | Pagination logic in Actions, not Controllers |

## Decisions Made

1. **Concrete `CursorPaginator` over interface** -- The Actions return `Illuminate\Pagination\CursorPaginator` (concrete) instead of `Illuminate\Contracts\Pagination\CursorPaginator` (interface). Reason: controllers need `through()` to transform items via ResourceData, and while the interface has `@method through()` in PHPDoc, the Larastan stubs for Eloquent Builder's `cursorPaginate()` return the concrete type anyway. Exception: `GetFamilyPartsAction` returns the interface because `toBase()->cursorPaginate()` declares the interface return type and the controller does not need `through()` (items are already stdClass).

2. **Positional args for `cursorPaginate` on HasMany relation** -- `GetStorageOptionPartsAction` uses positional arguments `cursorPaginate(min($perPage, self::MAX_PER_PAGE), ['*'], 'cursor', $cursor)` instead of named args. Reason: `HasMany` delegates `cursorPaginate` through `__call()`, and PHP named argument passing to `__call()` causes runtime errors. The other Actions call `cursorPaginate` directly on Builder, which has the method defined and supports named args.

3. **`toBase()->cursorPaginate()` for GetFamilyPartsAction** -- Converted the join query to use `toBase()` before `cursorPaginate()`. This eliminates the manual `map()` transformation and `getAttribute()` calls with their `@var` annotations. Items are now `stdClass` objects with typed properties, which PHPStan handles cleanly. Ordered by `storage_option_parts.id` (unique column) instead of `parts.name` (non-unique, cursor pagination requires unique ordering).

4. **Controller pattern: `through()` for ResourceData transformation** -- Controllers use `$cursorPaginator->through(fn ($model) => ResourceData::from($model)->toArray())` to map models to resource arrays while preserving pagination metadata. This keeps the controller thin (no manual envelope construction) while maintaining the ResourceData pattern.

5. **No FormRequests for pagination params** -- Per shipping order guidance, used typed parameters on Actions with defaults instead of creating FormRequests for index endpoints. Controllers read `per_page` and `cursor` from injected `Request` and pass as primitives.

## Quality Gauntlet

| Check | Result | Notes |
|---|---|---|
| lint:test | Pass | Rector renamed variables per conventions |
| phpstan | Pass | 0 errors at level max |
| deptrac | Pass | 0 violations |
| test | Pass | 469 tests, 1715 assertions |
| test:coverage | N/A | No coverage driver installed |
| test:feature-coverage | N/A | No coverage driver installed |
| mutation | N/A | No coverage driver installed |

## Showcase Readiness

Yes. The implementation follows Laravel native cursor pagination with minimal custom code. The response envelope (`data`, `next_cursor`, `prev_cursor`, `per_page`, URLs) is the standard Laravel format -- any frontend developer familiar with Laravel pagination will recognize it immediately. The per_page cap prevents abuse, the cursor ordering uses unique columns for stability, and the join query on `GetFamilyPartsAction` was simplified by eliminating the manual transformation layer. A senior architect would see idiomatic Laravel pagination with proper separation of concerns.

## Proposed Knowledge Updates

- **Learnings:** When calling `cursorPaginate()` on a HasMany relation (or any method forwarded through `__call()`), use positional arguments -- named arguments cause runtime errors because PHP cannot map named args to `__call()` parameters.
- **Learnings:** When converting a join query from `get()->map()` to `cursorPaginate()`, use `toBase()` first -- this returns `stdClass` items and eliminates the need for `getAttribute()` with type annotations.
- **Pulse:** Add cursor pagination to In-Progress Work as complete. Update test count from 417 to 469.

## Self-Debrief

### What Went Well

- Discovered that two Actions were already partially converted, which reduced scope
- The `toBase()->cursorPaginate()` approach for the join query was cleaner than trying to preserve the manual `map()` transformation
- All quality checks passed on first run after implementation

### What Went Poorly

- First PHPStan run failed because I used the `Contracts\CursorPaginator` interface which lacks `through()` -- should have checked the controller needs before choosing the return type
- First unit test run failed because of named args with `__call()` on `HasMany` -- cost an extra cycle to diagnose and fix

### Blind Spots

- Did not check if the existing `ResourceData::collection()` method is still used anywhere else -- it accepts `Collection` but is now bypassed for paginated endpoints. If other code calls it, no issue, but it is potentially dead code for these endpoints.

### Training Proposals

| Proposal | Context | Shift Evidence |
|---|---|---|
| When calling methods on Eloquent relations that are forwarded via `__call()`, always use positional arguments -- named args cause runtime errors | `HasMany::cursorPaginate()` with named params caused `Unknown named parameter` error | 2026-03-28-cursor-pagination |
| Before choosing a return type for an Action, check what the Controller needs to do with the result -- concrete types enable methods like `through()` that interfaces may only declare in PHPDoc | Chose interface first, then had to switch to concrete because `through()` is only on the concrete class for PHPStan | 2026-03-28-cursor-pagination |

---

## Logistics Director Evaluation

_Appended by the Logistics Director after reviewing the log. The sorter's sections above are not edited -- they stand as written._

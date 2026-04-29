# Warehouse Pulse — _Where Things Stand_

A consolidated, current-state assessment of the backend codebase. Updated by the Logistics Director at end-of-session. Not chronological — this is the **living snapshot** the Head Sorter reads before touching code.

**Rules:**

- Each section carries an `Assessed:` date — update it when you re-evaluate that section
- Sections not revisited keep their old date, making staleness visible
- Overwrite sections with current state — don't append history
- Keep entries factual and concise — one line per item

---

## Overall Health

**Rating:** 8.5/10
**Assessed:** 2026-04-16

Architecture is sound — PHPStan at max with zero errors (297 files), Deptrac with zero violations, 540 tests passing (1914 assertions), 11 coherent ADRs. All high-severity findings resolved. Recent deliveries: cross-set missing-parts shortfall endpoint (sibling to the set-completion gauge — both are bulk aggregation endpoints with matching five-query SQL-side discipline and envelope `ComputedResourceData`), action contract hygiene pass (5 Actions normalized for family-scoping + authorization-before-transaction), security hardening, audit remediation rounds 3-4. Coverage and mutation testing cannot be measured without a PHP coverage driver in the environment.

## Active Concerns

**Assessed:** 2026-04-29

> **FIRST ACTION NEXT SESSION:** dispatch the Head Sorter against `2026-04-30-laravel-137-deprecation-cleanup.md` (pre-filed in `.claude/records/permits/`). Resolves the 4 PHPStan errors from the Laravel 13.5→13.7 lockfile bump. Includes a Decisions Required gate on `config/database.php` — CEO call needed before editing that file (option B drops PHP 8.4 support; A and C are smaller).

| Concern | Severity | Status | Notes |
|---|---|---|---|
| Laravel 13.7 deprecation cascade — 4 PHPStan errors at level max | High | **Open — permit `2026-04-30-laravel-137-deprecation-cleanup` pre-filed for next-session first dispatch** | Surfaced 2026-04-29 by the PHP 8.5 alignment shift's `composer.lock` regeneration (`laravel/framework v13.5.0 → v13.7.0`). Files: `bootstrap/app.php:33`, `config/sanctum.php:85`, `config/database.php:64,84`. Two fixes mechanical; third has a Decisions Required gate. |
| `php8.5-pcov` not installed on dev host | Medium | Open — sudo-gated | One-line `sudo apt install php8.5-pcov` (deb.sury.org PPA). Dockerfile already commits the install for the Docker path. Until installed, `composer test:coverage` / `mutation` / `test:feature-coverage` bail on canonical PHP 8.5. |
| `covers()` mismatch in `CorsConfigTest` blocking feature-coverage | Low | Open — depends on `php8.5-pcov` for upstream visibility | `covers(HandleCors::class)` targets a vendor/ class outside `phpunit.feature-coverage.xml`'s `<source>`. Driver bail fires first today; surfaces as next blocker once driver lands. |
| Deferred mutation drill from 2026-04-19 L13 upgrade | Low | Open — depends on `php8.5-pcov` install + L13.7 cleanup | The L13 upgrade journal deferred mutation across three timed-out shifts. Becomes runnable after the driver install and PHPStan green. |
| Dockerfile build verification (`docker compose build backend`) | Low | Open — environmental | The 2026-04-29 PCOV install + PHP 8.5 alignment shifts both modified `docker/backend.Dockerfile`. Diff committed-ready; verification blocked in dev shell (no Docker daemon). |
| ~~`InvalidApiResponseException` not globally handled~~ | ~~High~~ | Resolved | 502 renderer registered in bootstrap/app.php; feature test confirms |
| ~~`ImportOwnedSetsAction` try-catch violates ADR-0003~~ | ~~High~~ | Resolved | ADR-0003 amended with approved exception documentation |
| ~~4 architecture tests produce no assertions (risky)~~ | ~~Medium~~ | Resolved | Counter assertions added; 83 tests, 1007 assertions, 0 risky |
| ~~UniqueConstraintViolationException try-catch undocumented~~ | ~~Medium~~ | Resolved | ADR-0003 amended with second approved exception (5 Actions) |
| ~~RoutingArchitectureTest missing 5 new routes~~ | ~~Medium~~ | Resolved | All 29 routes now in hardcoded enforcement list |
| ~~PHP coverage driver missing from environment (single-driver framing)~~ | ~~Medium~~ | Superseded 2026-04-29 | Investigation revealed the real cause was host `php` aliased to 8.5 with no pcov build (while `php8.4-pcov` was already installed). Resolved by Path B (canonical PHP 8.5 across dev/prod); replaced by the more granular Open Items above. |

## In-Progress Work

**Assessed:** 2026-04-16

| Work Item | Status | Next Step |
|---|---|---|
| Stud & Sort Logistics setup | Complete | CLAUDE.md, agents, docs, records all in place |
| Baseline audit | Complete | Report filed; evaluation appended; pulse updated |
| Audit remediation (round 1) | Complete | 2 high, 1 medium, 3 low findings resolved |
| Routine sweep audit | Complete | 5 findings (0 high, 2 medium, 3 low) — all remediated |
| Audit remediation (round 2) | Complete | ADR-0003 try-catch docs, test gaps, code quality |
| Queue-based imports | Complete | ImportJob model, async Rebrickable imports with race condition hardening |
| Response caching | Complete | ETag + application-level caching for read endpoints |
| Cursor pagination | Complete (partial) | Only `/family/parts` retains cursor pagination; three other list endpoints reverted to unbounded |
| Test gap sweep | Complete | Policy, factory, and resource test gaps closed |
| Job layer hardening | Complete | JobArchitectureTest added; conventions documented |
| Audit remediation (round 3) | Complete | 2026-03-30 full sweep — 6 findings resolved |
| Audit remediation (round 4) | Complete | 2026-04-11 post-delivery sweep — ADR-0003, CLAUDE.md, CI, pulse |
| Action contract hygiene | Complete | 2026-04-16 — 5 Actions normalized (family-scoped signatures, authorization-before-transaction) |
| Master shopping list endpoint | Complete | 2026-04-16 — `GET /family-sets/missing-parts` bulk shortfall aggregation with `unknownFamilySetIds` honesty contract |

## Pattern Maturity

**Assessed:** 2026-04-16

| Pattern | Maturity | Evidence |
|---|---|---|
| Action layer (35 classes) | Battle-tested | Architecture tests guard it; all pass. Three approved try-catch exceptions documented in ADR-0003: partial-failure (ImportOwnedSetsAction), UniqueConstraintViolationException upsert (5 Actions), and race-condition guard (StartImportAction) |
| Service layer (2 classes) | Battle-tested | Contract interfaces, Deptrac boundaries hold, no facade or model leakage |
| ResourceData pattern (18 classes) | Battle-tested | All have `from()` factories, EAGER_LOAD where needed. ComputedResourceData (ADR-0010) handles DTO-sourced responses; `FamilyMissingPartsResourceData` is the latest envelope application |
| Explicit cascade deletion | Battle-tested | MigrationArchitectureTest + CascadeRelationArchitectureTest confirm compliance |
| Thin controllers | Battle-tested | No constructors, no try-catch, method injection only. ControllerArchitectureTest confirms |
| Job layer (1 class) | Established | JobArchitectureTest guards conventions; thin wrapper pattern documented in CLAUDE.md |
| Bulk aggregation endpoints (2 endpoints) | Established | `/family-sets/completion` and `/family-sets/missing-parts` share the five-query SQL-side discipline — no PHP summation, database portability preserved |

## Tech Debt

**Assessed:** 2026-03-31

| Item | Severity | Notes |
|---|---|---|
| ~~`InvalidApiResponseException` handler gap~~ | ~~High~~ | Resolved — 502 renderer registered, feature test confirms |
| ~~ADR-0003 try-catch exception undocumented~~ | ~~High~~ | Resolved — ADR-0003 amended with approved exception |
| ~~`FamilyPolicyTest` missing policy method tests~~ | ~~Low~~ | Resolved — all 9 policy methods now have unit tests |
| ~~`decisions.md` broken ADR-000 link~~ | ~~Low~~ | Resolved — link fixed |
| `GetFamilyPartsAction` returns raw array (no ResourceData) | Low | Only endpoint bypassing the pattern without documentation |
| `RegisterUserData::familyName` empty-string on invite-code path | Low | Now nullable — passes null when family_name absent |

## Seeds

**Assessed:** 2026-03-25

| Seed | Trigger | What It Means |
|---|---|---|
| ~~Formal pulse baseline~~ | ~~First Inventory Auditor run~~ | ~~Done — 2026-03-25 audit established baseline~~ |
| Learnings bootstrap | First Head Sorter shift | Document gotchas discovered during first session under new regime |
| Coverage infrastructure | Install pcov or xdebug | Unblocks coverage measurement, mutation testing, and full quality metrics |

## Quality Metrics

**Assessed:** 2026-04-29

| Metric | Value | Threshold |
|---|---|---|
| Unit coverage | 100.0% (measured 2026-04-29 PCOV-install shift, 8.5-via-shim) — currently unable to re-measure on canonical 8.5 (sudo-gated `php8.5-pcov` install) | 100% |
| Feature coverage | Unable to measure (`covers()` mismatch in `CorsConfigTest` + `php8.5-pcov` not installed on canonical 8.5) | 90% |
| Mutation score | 76.97% (measured 2026-04-29 PCOV-install shift, 8.5-via-shim) — currently unable to re-measure on canonical 8.5 | 76% |
| Architecture tests | 90 passed (1678 assertions) | All passing |
| PHPStan | Level max, **4 errors** (Laravel 13.5→13.7 deprecation cascade — pre-filed permit `2026-04-30-laravel-137-deprecation-cleanup` resolves) | Level max, zero errors |
| Deptrac | 0 violations / 651 allowed | Zero violations |
| Full test suite | 569 tests, 2330 assertions | All passing |

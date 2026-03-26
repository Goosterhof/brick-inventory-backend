# Warehouse Pulse — _Where Things Stand_

A consolidated, current-state assessment of the backend codebase. Updated by the Logistics Director at end-of-session. Not chronological — this is the **living snapshot** the Head Sorter reads before touching code.

**Rules:**

- Each section carries an `Assessed:` date — update it when you re-evaluate that section
- Sections not revisited keep their old date, making staleness visible
- Overwrite sections with current state — don't append history
- Keep entries factual and concise — one line per item

---

## Overall Health

**Rating:** 8/10
**Assessed:** 2026-03-26

Architecture is sound — PHPStan at max with zero errors (171 files), Deptrac with zero violations, 417 tests passing (1472 assertions), 9 coherent ADRs. All high-severity findings resolved. Two medium governance gaps from routine sweep remediated (ADR-0003 try-catch documentation, RoutingArchitectureTest route coverage). Coverage and mutation testing cannot be measured without a PHP coverage driver in the environment.

## Active Concerns

**Assessed:** 2026-03-26

| Concern | Severity | Status | Notes |
|---|---|---|---|
| ~~`InvalidApiResponseException` not globally handled~~ | ~~High~~ | Resolved | 502 renderer registered in bootstrap/app.php; feature test confirms |
| ~~`ImportOwnedSetsAction` try-catch violates ADR-0003~~ | ~~High~~ | Resolved | ADR-0003 amended with approved exception documentation |
| ~~4 architecture tests produce no assertions (risky)~~ | ~~Medium~~ | Resolved | Counter assertions added; 83 tests, 1007 assertions, 0 risky |
| ~~UniqueConstraintViolationException try-catch undocumented~~ | ~~Medium~~ | Resolved | ADR-0003 amended with second approved exception (5 Actions) |
| ~~RoutingArchitectureTest missing 5 new routes~~ | ~~Medium~~ | Resolved | All 29 routes now in hardcoded enforcement list |
| PHP coverage driver missing from environment | Medium | Open | xdebug/pcov not installed; coverage and mutation testing cannot run |

## In-Progress Work

**Assessed:** 2026-03-26

| Work Item | Status | Next Step |
|---|---|---|
| Stud & Sort Logistics setup | Complete | CLAUDE.md, agents, docs, records all in place |
| Baseline audit | Complete | Report filed; evaluation appended; pulse updated |
| Audit remediation | Complete | 2 high, 1 medium, 3 low findings resolved |
| Routine sweep audit | Complete | 5 findings (0 high, 2 medium, 3 low) — all remediated |

## Pattern Maturity

**Assessed:** 2026-03-26

| Pattern | Maturity | Evidence |
|---|---|---|
| Action layer (31 classes) | Battle-tested | Architecture tests guard it; all pass. Two approved try-catch exceptions documented in ADR-0003: partial-failure (ImportOwnedSetsAction) and UniqueConstraintViolationException upsert (5 Actions) |
| Service layer (2 classes) | Battle-tested | Contract interfaces, Deptrac boundaries hold, no facade or model leakage |
| ResourceData pattern (11 classes) | Battle-tested | All have `from()` factories, EAGER_LOAD where needed. One endpoint (family/parts) bypasses pattern without documentation |
| Explicit cascade deletion | Battle-tested | MigrationArchitectureTest + CascadeRelationArchitectureTest confirm compliance |
| Thin controllers | Battle-tested | No constructors, no try-catch, method injection only. ControllerArchitectureTest confirms |

## Tech Debt

**Assessed:** 2026-03-26

| Item | Severity | Notes |
|---|---|---|
| ~~`InvalidApiResponseException` handler gap~~ | ~~High~~ | Resolved — 502 renderer registered, feature test confirms |
| ~~ADR-0003 try-catch exception undocumented~~ | ~~High~~ | Resolved — ADR-0003 amended with approved exception |
| ~~`FamilyPolicyTest` missing policy method tests~~ | ~~Low~~ | Resolved — all 9 policy methods now have unit tests |
| `decisions.md` broken ADR-000 link | Low | References nonexistent `ADR-000.md` |
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

**Assessed:** 2026-03-26

| Metric | Value | Threshold |
|---|---|---|
| Unit coverage | Unable to measure (no coverage driver) | 100% |
| Feature coverage | Unable to measure (no coverage driver) | 80% |
| Mutation score | Unable to measure (no coverage driver) | 75% |
| Architecture tests | 18 files, 83 passed, 0 risky, 1 warning (1007 assertions) | All passing |
| PHPStan | Level max, 0 errors (171 files) | Level max, zero errors |
| Deptrac | 0 violations (494 allowed, 398 uncovered) | Zero violations |
| Full test suite | 417 tests, 1472 assertions | All passing |

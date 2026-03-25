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
**Assessed:** 2026-03-25

Architecture is sound — PHPStan at max with zero errors, Deptrac with zero violations, 347 tests passing (1187 assertions), 9 coherent ADRs. Both high-severity findings from baseline audit resolved. Coverage and mutation testing cannot be measured without a PHP coverage driver in the environment.

## Active Concerns

**Assessed:** 2026-03-25

| Concern | Severity | Status | Notes |
|---|---|---|---|
| ~~`InvalidApiResponseException` not globally handled~~ | ~~High~~ | Resolved | 502 renderer registered in bootstrap/app.php; feature test confirms |
| ~~`ImportOwnedSetsAction` try-catch violates ADR-0003~~ | ~~High~~ | Resolved | ADR-0003 amended with approved exception documentation |
| ~~4 architecture tests produce no assertions (risky)~~ | ~~Medium~~ | Resolved | Counter assertions added; 83 tests, 904 assertions, 0 risky |
| PHP coverage driver missing from environment | Medium | Open | xdebug/pcov not installed; coverage and mutation testing cannot run |

## In-Progress Work

**Assessed:** 2026-03-25

| Work Item | Status | Next Step |
|---|---|---|
| Stud & Sort Logistics setup | Complete | CLAUDE.md, agents, docs, records all in place |
| Baseline audit | Complete | Report filed; evaluation appended; pulse updated |
| Audit remediation | Complete | 2 high, 1 medium, 3 low findings resolved |

## Pattern Maturity

**Assessed:** 2026-03-25

| Pattern | Maturity | Evidence |
|---|---|---|
| Action layer (26 classes) | Battle-tested | Architecture tests guard it; all pass. Partial-failure try-catch in ImportOwnedSetsAction documented as approved exception in ADR-0003 |
| Service layer (2 classes) | Battle-tested | Contract interfaces, Deptrac boundaries hold, no facade or model leakage |
| ResourceData pattern (11 classes) | Battle-tested | All have `from()` factories, EAGER_LOAD where needed. One endpoint (family/parts) bypasses pattern without documentation |
| Explicit cascade deletion | Battle-tested | MigrationArchitectureTest + CascadeRelationArchitectureTest confirm compliance |
| Thin controllers | Battle-tested | No constructors, no try-catch, method injection only. ControllerArchitectureTest confirms |

## Tech Debt

**Assessed:** 2026-03-25

| Item | Severity | Notes |
|---|---|---|
| ~~`InvalidApiResponseException` handler gap~~ | ~~High~~ | Resolved — 502 renderer registered, feature test confirms |
| ~~ADR-0003 try-catch exception undocumented~~ | ~~High~~ | Resolved — ADR-0003 amended with approved exception |
| `decisions.md` broken ADR-000 link | Low | References nonexistent `ADR-000.md` |
| `FamilyPolicyTest` missing `viewParts`/`viewStats` tests | Low | Trivial methods untested; would be a coverage gap if measurable |
| `GetFamilyPartsAction` returns raw array (no ResourceData) | Low | Only endpoint bypassing the pattern without documentation |

## Seeds

**Assessed:** 2026-03-25

| Seed | Trigger | What It Means |
|---|---|---|
| ~~Formal pulse baseline~~ | ~~First Inventory Auditor run~~ | ~~Done — 2026-03-25 audit established baseline~~ |
| Learnings bootstrap | First Head Sorter shift | Document gotchas discovered during first session under new regime |
| Coverage infrastructure | Install pcov or xdebug | Unblocks coverage measurement, mutation testing, and full quality metrics |

## Quality Metrics

**Assessed:** 2026-03-25

| Metric | Value | Threshold |
|---|---|---|
| Unit coverage | Unable to measure (no coverage driver) | 100% |
| Feature coverage | Unable to measure (no coverage driver) | 80% |
| Mutation score | Unable to measure (no coverage driver) | 75% |
| Architecture tests | 15 files, 83 passed, 0 risky, 1 warning (904 assertions) | All passing |
| PHPStan | Level max, 0 errors (155 files) | Level max, zero errors |
| Deptrac | 0 violations (431 allowed, 336 uncovered) | Zero violations |

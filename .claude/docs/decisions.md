# Decision Ledger — _Why We Built It This Way_

Architecture decisions that shaped the warehouse. Each records what was chosen, what was rejected, and what enforces it. Full records live in `docs/adr/`.

**Start here:** [ADR-000](ADR-000.md) explains why this project exists, who the audience is, and how decisions should be evaluated.

Every significant decision goes through a **Logistics Director-mediated review**: the Head Sorter proposes, the Director challenges, the CEO approves. New ADRs use the [decision record template](.decision-record-template.md).

## Decision Index

| # | Decision | Date | Status |
|---|---|---|---|
| [000](ADR-000.md) | Why this warehouse exists and how decisions are made | 2026-03-21 | Accepted |
| 0001 | Session-based SPA auth, not tokens | — | Accepted |
| 0002 | Single-tier authorization with three-layer defense | — | Accepted |
| 0003 | Actions for business logic, Services for HTTP only | — | Accepted |
| 0004 | Explicit cascade deletion, not database-level | — | Accepted |
| 0005 | No mass assignment ($fillable/$guarded) | — | Accepted |
| 0006 | DTOFormRequest + custom ResourceData | — | Accepted |
| 0007 | #[Config] attributes, not helpers/facades | — | Accepted |
| 0008 | Explicit routes, not apiResource | — | Accepted |
| 0009 | Thin controllers with method injection only | — | Accepted |

_Note: These ADRs predate the Stud & Sort regime. Dates reflect original authorship — full reasoning in `docs/adr/NNNN-*.md`._

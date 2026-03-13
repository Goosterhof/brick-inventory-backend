# Project Instructions

## Build Philosophy

You are a Technic LEGO master builder. You know where every pin goes.

- **Build sturdy** — Every piece connects with purpose. No loose fits, no wobbly joints. Code must be solid and reliable.
- **Test with rigorous intent** — Stress-test every build. If it can break, find out before it ships. Write tests that prove the build holds, not just that it compiles.
- **Small cogs, big machine** — Create small, focused pieces that interlock into the whole. Each Action, Service, and Controller is a single cog with one job.
- **When a cog gets too big, make it smaller** — If a class, method, or action is doing too much, split it. Extract. Decompose. A Technic build is hundreds of small parts, not a few massive ones.
- **Every pin has a place** — No unused parts left on the table. No dead code, no orphaned methods, no parameters that go nowhere.

## Builder's Discipline

The architecture tests guard the shape. This section guards the builder.

### Complexity Triggers
- **>4 constructor dependencies** — the cog is too big. Split the action or extract a sub-action.
- **>20 lines in a method** — find the seam, extract a private method or delegate to another action.
- **>3 public methods on a non-controller class** — it's doing more than one job.

### Refactor Signals
- **3+ actions doing similar orchestration** — extract a shared pattern. Not before 3 — premature abstraction is worse than duplication.
- **A service growing a second responsibility** — if it fetches from two unrelated APIs, split it into two services.
- **A test that needs >5 mocks to arrange** — the code under test has too many collaborators.

### Challenge the Pattern
- Before following a convention, check if existing code still benefits from it. Conventions serve the build, not the other way around.
- If a pattern was designed for 5 models but the project now has 15, ask whether it still scales.
- If a skill's instructions conflict with what the codebase actually does, trust the codebase — then update the skill.

### Prune Dead Pins
- After completing a feature, check for: unused imports, orphaned interfaces, routes with no controller, factory states never used in tests.
- If a skill hasn't been updated to match current code patterns, trim or rewrite it.
- If an architecture test enforces a rule that no longer applies, remove it — stale rules are false confidence.

## Overview

LEGO inventory management system. The goal is to provide a list of parts needed to build a specific set, along with the physical storage location (drawer) where each part is stored.

## Tech Stack

- Laravel 12 (API-only, no frontend)
- PHP 8.4+
- SQLite for local development
- Laravel Sanctum for authentication (session-based SPA auth, NOT API tokens)

## Authentication

Uses **session-based SPA authentication** with Laravel Sanctum's stateful middleware:

- Login/Register use `Auth::login($user)` to create a session (no token creation)
- Logout uses `Auth::guard('web')->logout()` + session invalidation
- Controllers return `ProfileResourceData` directly (not wrapped in `{user, token}`)
- CSRF is excluded for `api/*` routes since the frontend doesn't fetch CSRF cookies
- `SANCTUM_STATEFUL_DOMAINS` must include the frontend's `host:port` (e.g., `localhost:5173`)
- Feature tests use `$this->actingAs($user)` (not `Sanctum::actingAs()`)

### Gotcha
- `$request->session()` throws if no session middleware is active; guard with `$request->hasSession()`

## Deployment

- **Hosting**: Railway
- **Production URL**: https://api.brick-inventory.com
- **DNS**: Cloudflare (proxy disabled, Railway handles SSL)

### FrankenPHP Worker
- `public/frankenphp-worker.php` runs BEFORE the autoloader — never use Laravel classes in it
- `composer lint` (Rector) may incorrectly modify this file — always revert changes to it

## Architecture

### Layer Map

```
HTTP Request
  │
  ├─ Middleware ─── EnsureFamilyOwnership (tenant isolation)
  │
  ▼
Controller (thin, no constructor, method injection only)
  │  returns JsonResponse or array
  │
  ├── FormRequest ──► validates + becomes DTO via interface contract
  │
  ▼
Action (business logic, orchestration)
  │  single execute() method, final readonly
  │  can call other Actions and Services
  │
  ├── Service ──► external HTTP only (no DB, no Actions)
  │
  ▼
Model (no $fillable/$guarded, explicit property assignment)
  │  @property PHPDoc, cascadeRelations()
  │
  ▼
ResourceData (final readonly DTO for API responses)
     from(Model), requiredRelations(), toResponse()
```

### Cog Inventory

| Layer | Count | Key Examples |
|-------|-------|--------------|
| Controllers | 10 | StorageOption, FamilySet, Set, Auth (4) |
| Actions | 23 | CRUD per domain + Sync (4) + BrickIdentification |
| Services | 2 | RebrickableService, BrickognizeService |
| Models | 9 | User, Family, Set, Part, Color, StorageOption, ... |
| Policies | 6 | Per tenant-scoped resource |
| Arch Tests | 18 | One per layer + cross-cutting concerns |
| Migrations | 16 | Schema evolution |

### Multi-tenancy

Tenant isolation by "families" — shared database with `family_id` column. No separate domains per tenant.

### Authorization

Single-tier policy model with three-layer defense in depth:

1. **`EnsureFamilyOwnership` middleware** — returns 404 if resource doesn't belong to user's family
2. **Policies** — `final readonly`, enforced via `->can()` on routes. No `Gate` injection, no `->authorize()`
3. **FormRequest closure rules** — body parameter validation the middleware can't reach

### Decisions

Key architectural decisions are recorded in `docs/adr/`. Each ADR captures what was chosen, what was rejected, and what enforces it. See the [ADR index](docs/adr/README.md).

Use `/conventions` skill for detailed patterns on Action vs Service responsibilities, exception handling, and architecture rules.

## Commands

| Command | Description |
|---------|-------------|
| `composer dev` | Start development server |
| `composer test` | Run tests |
| `composer test:arch` | Run architecture tests only |
| `composer test:coverage` | Run unit tests with 100% coverage (Actions & Services) |
| `composer test:feature-coverage` | Run feature tests with 80% coverage (Controllers) |
| `composer lint` | Run Rector + Pint (fix mode) |
| `composer lint:test` | Run Rector + Pint (dry-run) |
| `composer phpstan` | Run static analysis |
| `composer deptrac` | Run layer dependency analysis |
| `composer mutation` | Run mutation testing (requires pcov) |

## Before Committing

Before creating any commit, always run — in order:

1. `composer lint` — fix code style
2. `composer phpstan` — check for type errors
3. `composer test` — ensure all tests pass

All three must pass before committing.

## Skills

When creating files, **always use the matching skill** in `.claude/skills/`. Each skill knows the project's conventions and will read existing code for patterns. Use `/conventions` for architecture reference.

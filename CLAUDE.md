# Project Instructions

## Build Philosophy

You are a Technic LEGO master builder. You know where every pin goes.

- **Build sturdy** — Every piece connects with purpose. No loose fits, no wobbly joints. Code must be solid and reliable.
- **Test with rigorous intent** — Stress-test every build. If it can break, find out before it ships. Write tests that prove the build holds, not just that it compiles.
- **Small cogs, big machine** — Create small, focused pieces that interlock into the whole. Each Action, Service, and Controller is a single cog with one job.
- **When a cog gets too big, make it smaller** — If a class, method, or action is doing too much, split it. Extract. Decompose. A Technic build is hundreds of small parts, not a few massive ones.
- **Every pin has a place** — No unused parts left on the table. No dead code, no orphaned methods, no parameters that go nowhere.

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

### Multi-tenancy
- Tenant isolation based on "families"
- Shared database with `family_id` column where needed
- No separate domains per tenant

### Authorization

Uses a **single-tier** policy model with three-layer defense in depth:

1. **`EnsureFamilyOwnership` middleware** — Tenant isolation. Returns 404 if the resource doesn't belong to the user's family. Applied to all tenant-scoped routes.
2. **Policies** (`app/Policies/`) — Permission checks. `final readonly` classes, auto-discovered. Enforced via `->can()` on routes (e.g., `->can('view', 'storage_option')` or `->can('viewAny', StorageOption::class)`). Controllers must **not** inject `Gate` or call `->authorize()` — authorization lives entirely in the routing layer.
3. **FormRequest closure rules** — Body parameter validation that the middleware cannot reach (e.g., validating `parent_id` belongs to the user's family).

Architecture tests in `tests/Architecture/PolicyArchitectureTest.php` enforce naming, `final readonly`, no Gate injection in controllers, no `->authorize()` calls, and `can:` middleware presence on all authorized routes.

### Code Patterns
- **Action classes**: Business logic and orchestration (single-responsibility)
- **Service classes**: External API connections only (e.g., `RebrickableService`)
- **ResourceData classes**: DTO-style classes for API responses
- **DTOFormRequest pattern**: Form Requests that act as DTOs with interface contracts
- **Standard Laravel**: Controllers, Models for the rest

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

**IMPORTANT**: When creating files, use the corresponding skill. Skills contain detailed templates and conventions.

| File Path Pattern | Skill | Description |
|-------------------|-------|-------------|
| `app/Actions/**/*Action.php` | `/action` | Create Action classes |
| `app/Http/Controllers/*Controller.php` | `/controller` | Create resource controllers with CRUD operations |
| `app/Http/Requests/*Request.php` | `/form-request` | Create Form Requests with DTOFormRequest pattern |
| `app/Http/Resources/*ResourceData.php` | `/resource-data` | Create ResourceData classes for API responses |
| `app/Models/*.php` | `/model` | Generate models from migrations |
| `app/Services/*Service.php` | `/service` | Create Service classes for external APIs |
| `database/factories/*Factory.php` | `/factory` | Generate factories from models |
| `database/migrations/*.php` | `/migration` | Generate migrations from model names |
| `tests/Unit/**/*Test.php` | `/unit-test` | Create or run unit tests |
| `tests/Feature/**/*Test.php` | `/feature-test` | Generate feature tests for controllers |

Also available: `/conventions` — Architecture patterns and code conventions reference.

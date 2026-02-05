# Project Instructions

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

### Gotchas
- `$request->session()` throws if no session middleware is active; guard with `$request->hasSession()`
- `public/frankenphp-worker.php` runs BEFORE the autoloader — never use Laravel classes in it
- `composer lint` (Rector) may incorrectly modify `frankenphp-worker.php` — always revert changes to it

## Deployment

- **Hosting**: Railway
- **Production URL**: https://api.brick-inventory.com
- **DNS**: Cloudflare (proxy disabled, Railway handles SSL)

## Architecture

### Multi-tenancy
- Tenant isolation based on "families"
- Shared database with `family_id` column where needed
- No separate domains per tenant

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

## Code Quality

After making any changes to PHP files, always run:

```bash
composer lint
```

## Before Committing

Before creating any commit, always:

1. Run `composer lint` to fix code style
2. Run `composer phpstan` to check for type errors
3. Run `composer test` to ensure all tests pass

All must pass before committing.

## Skills

Use skills for file creation - they contain detailed templates and conventions.

| Skill | Description |
|-------|-------------|
| `/action` | Create Action classes |
| `/controller` | Create resource controllers with CRUD operations |
| `/conventions` | Architecture patterns and code conventions reference |
| `/factory` | Generate factories from models |
| `/feature-test` | Generate feature tests for controllers |
| `/form-request` | Create Form Requests with DTOFormRequest pattern |
| `/migration` | Generate migrations from model names |
| `/model` | Generate models from migrations |
| `/resource-data` | Create ResourceData classes for API responses |
| `/service` | Create Service classes for external APIs |
| `/unit-test` | Create or run unit tests |

### Required Skill Usage

**IMPORTANT**: When creating files in these directories, use the corresponding skill:

| File Path Pattern | Required Skill |
|-------------------|----------------|
| `app/Actions/**/*Action.php` | `/action` |
| `app/Http/Controllers/*Controller.php` | `/controller` |
| `app/Http/Requests/*Request.php` | `/form-request` |
| `app/Http/Resources/*ResourceData.php` | `/resource-data` |
| `app/Models/*.php` | `/model` |
| `app/Services/*Service.php` | `/service` |
| `database/factories/*Factory.php` | `/factory` |
| `database/migrations/*.php` | `/migration` |
| `tests/Unit/**/*Test.php` | `/unit-test` |
| `tests/Feature/**/*Test.php` | `/feature-test` |

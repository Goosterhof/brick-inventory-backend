# Project Instructions

## Overview

LEGO inventory management system. The goal is to provide a list of parts needed to build a specific set, along with the physical storage location (drawer) where each part is stored.

## Tech Stack

- Laravel 12 (API-only, no frontend)
- PHP 8.4+
- SQLite for local development
- Laravel Sanctum for authentication

## Architecture

### Multi-tenancy
- Tenant isolation based on "families"
- Shared database with `family_id` column where needed
- No separate domains per tenant

### Code Patterns
- **Action classes**: For internal business logic (single-responsibility)
- **Service classes**: For external API connections (e.g., `RebrickableService`)
- **Standard Laravel**: Controllers, Models, API Resources for the rest

### API Structure
- Standard Laravel RESTful API
- Resource controllers for CRUD operations

## Testing

Uses Pest PHP with the following conventions:

- **Feature tests**: For API endpoints, using `actingAs()` for authentication
- **Unit tests**: For Action and Service classes
- **Structure**: Use `describe` blocks with `it('should ...')` syntax
- **Assertions**: Use `expect()` style
- **Architecture tests**: Located in `tests/Architecture/` to enforce code standards

Example:
```php
describe('CreateSetAction', function () {
    it('should create a set with valid data', function () {
        // arrange
        // act
        // assert with expect()
    });
});
```

### Architecture Rules

The following rules are enforced via Pest architecture tests:

- Controllers must end with `Controller`
- Models must extend `Illuminate\Database\Eloquent\Model`
- DTOs must end with `Data`, be `final`, and `readonly`
- Requests must end with `Request`
- Services must end with `Service`
- All files must declare strict types
- No debugging statements (`dd`, `dump`, `var_dump`, `ray`)

## Commands

| Command | Description |
|---------|-------------|
| `composer dev` | Start development server |
| `composer test` | Run tests |
| `composer test:arch` | Run architecture tests only |
| `composer lint` | Run Rector + Pint (fix mode) |
| `composer lint:test` | Run Rector + Pint (dry-run) |
| `composer phpstan` | Run static analysis |

## Code Quality

After making any changes to PHP files, always run:

```bash
composer lint
```

This runs Rector (refactoring) followed by Pint (formatting) to ensure code quality.

## Before Committing

Before creating any commit, always:

1. Run `composer lint` to fix code style
2. Run `composer phpstan` to check for type errors
3. Run `composer test` to ensure all tests pass

All must pass before committing. If any fail, fix them before proceeding.

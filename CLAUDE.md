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

### Model Conventions
- **No mass assignment**: Models must NOT have `$fillable` or `$guarded` properties (enforced by architecture tests)
- **Explicit property assignment**: Always assign properties individually for type safety
- **PHPDoc annotations**: All models must have `@property` annotations for every column
- **Relationships**: Use PHPDoc return type annotations like `@return BelongsTo<Model, $this>`
- **Tenant models**: Models with `family_id` automatically get a `family()` BelongsTo relationship

Example property assignment (instead of mass assignment):
```php
// Correct - explicit assignment
$model = new Model();
$model->name = $data['name'];
$model->save();

// Wrong - mass assignment
$model = Model::create(['name' => $data['name']]);
```

### API Structure
- Standard Laravel RESTful API
- Resource controllers for CRUD operations

## Testing

Uses Pest PHP with the following conventions:

- **Feature tests**: For API endpoints, using `actingAs()` for authentication
- **Unit tests**: For Action and Service classes (must NOT touch the database - use mocks)
- **Structure**: Use `describe` blocks with `it('should ...')` syntax
- **Assertions**: Use `expect()` style
- **Architecture tests**: Located in `tests/Architecture/` to enforce code standards

**Important**: When creating or running unit tests, use the `/unit-test` skill which contains detailed conventions and mocking patterns.

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
- Models must NOT have `$fillable` or `$guarded` properties (no mass assignment)
- Models must have `@property` PHPDoc annotations
- DTOs must end with `Data`, be `final`, and `readonly`
- Requests must end with `Request`
- Services must end with `Service`
- Actions must end with `Action` and only have `execute` as public method
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

## Skills

The following custom skills are available:

| Skill | Description |
|-------|-------------|
| `/migration` | Generate a migration from a model name. Use `/migration ModelName` to create a migration. Auto-detects create vs modify, supports pivot tables. |
| `/model` | Generate an Eloquent model from an existing migration. Use `/model ModelName` to create a model. |
| `/unit-test` | Create or run unit tests. Use `/unit-test ClassName` to generate tests for a class, or `/unit-test --run` to run all tests. |
| `/action` | Create Action classes. Use `/action CreateUser` to generate an action. Infers type from verb (Create, Update, Delete, Get). |
| `/controller` | Create a resource controller. Use `/controller ModelName` to generate a controller with CRUD operations, routes, and Action placeholders. |

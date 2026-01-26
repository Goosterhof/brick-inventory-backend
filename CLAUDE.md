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
- **Action classes**: For internal business logic and orchestration (single-responsibility)
- **Service classes**: For external API connections only - no business logic (e.g., `RebrickableService`)
- **ResourceData classes**: DTO-style classes for API responses (extend `ResourceData` base class)
- **DTOFormRequest pattern**: Form Requests that act as DTOs with interface contracts
- **Standard Laravel**: Controllers, Models for the rest

### Action vs Service Responsibilities

**Services** should ONLY handle external API communication:
- HTTP requests/responses
- Response parsing and validation
- Custom exception handling for API errors

**Actions** handle business logic and orchestration:
- Database operations (via injected models)
- Calling services for external data
- Calling other actions for sub-operations
- Loading relationships

Example flow:
```
Controller
  └─ GetSetPartsAction (orchestration)
       ├─ LegoDataService.fetchSet() → HTTP call only
       ├─ LegoDataService.fetchSetParts() → HTTP call only
       ├─ UpsertSetAction → DB operation
       └─ StoreSetPartsAction → DB operation
```

### Avoiding Action Overlap

When multiple actions need similar logic, one should delegate to the other rather than duplicating code:
- `GetSetAction` delegates to `UpsertSetAction` for set creation
- Periodically review actions for overlap, especially within the same domain

### Form Requests as DTOs

Form Requests extend `DTOFormRequest` and implement interfaces with PHP 8.4 property hooks:

```php
// Interface defines the contract
interface CreateProductInterface
{
    public string $name { get; }
    public ?string $description { get; }
}

// Request implements validation + DTO
final readonly class CreateProductRequest extends DTOFormRequest implements CreateProductInterface
{
    public const string NAME = 'name';

    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}

    public static function rules(Request $request): array
    {
        return [self::NAME => ['required', 'string']];
    }

    protected static function toDTO(Request $request): static
    {
        return new self(name: $request->string(self::NAME)->toString());
    }
}
```

Actions accept interfaces (not concrete classes) for testability:

```php
class CreateProductAction
{
    public function execute(CreateProductInterface $data): Product { ... }
}
```

Use `/form-request` skill for detailed patterns and templates.

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

### Database Conventions

#### Migration Structure
- Use anonymous classes: `return new class extends Migration`
- Add `void` return type to `up()`, `down()`, and Schema callbacks
- Always implement both `up()` and `down()` methods

#### Foreign Keys
- Use `->constrained()` for foreign keys
- **No cascade deletes**: Never use `onDelete('cascade')` or `cascadeOnDelete()`
- Deletion cascading must be handled in Action classes (business logic), not at the database level
- This prevents unintended data loss and keeps deletion logic explicit and controllable

Example:
```php
// Correct - no cascade
$table->foreignId('cabinet_id')->constrained();
$table->foreignId('family_id')->nullable()->constrained();

// Wrong - cascade delete
$table->foreignId('cabinet_id')->constrained()->onDelete('cascade');
```

#### Tenant Scoping (family_id)
Include `family_id` for user-owned/tenant-scoped data:
- Storage-related: drawers, cabinets, shelves, storage options
- User collections: inventories, wishlists, builds
- User preferences: settings, configurations

Exclude `family_id` for shared/reference data:
- LEGO reference data: colors, parts, sets, themes, categories
- System data: jobs, cache, tokens

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
- Services must NOT depend on Actions (separation of concerns)
- Services must NOT use Models directly (no persistence logic in services)
- Actions must end with `Action` and only have `execute` as public method
- All files must declare strict types
- No debugging statements (`dd`, `dump`, `var_dump`, `ray`)
- Migrations must use anonymous classes
- Migrations must have `void` return types on `up()` and `down()`
- Migrations must declare strict types
- Migrations must NOT use cascade deletes (`onDelete('cascade')` or `cascadeOnDelete()`)

## Commands

| Command | Description |
|---------|-------------|
| `composer dev` | Start development server |
| `composer test` | Run tests |
| `composer test:arch` | Run architecture tests only |
| `composer test:coverage` | Run unit tests with 100% coverage requirement (Actions & Services) |
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
| `/factory` | Generate a factory from an existing model. Use `/factory ModelName` to create a factory with faker methods and state helpers. |
| `/migration` | Generate a migration from a model name. Use `/migration ModelName` to create a migration. Auto-detects create vs modify, supports pivot tables. |
| `/model` | Generate an Eloquent model from an existing migration. Use `/model ModelName` to create a model. |
| `/unit-test` | Create or run unit tests. Use `/unit-test ClassName` to generate tests for a class, or `/unit-test --run` to run all tests. |
| `/resource-data` | Create a ResourceData class for API responses. Use `/resource-data ModelName` to generate a ResourceData class for a model. |
| `/form-request` | Create Form Requests using the DTOFormRequest pattern with interface contracts. Includes templates, type mapping, and checklist. |
| `/action` | Create Action classes. Use `/action CreateUser` to generate an action. Infers type from verb (Create, Update, Delete, Get). |
| `/controller` | Create a resource controller. Use `/controller ModelName` to generate a controller with CRUD operations, routes, and Action placeholders. |
| `/feature-test` | Generate feature tests for API controllers. Use `/feature-test ControllerName` to generate tests for all endpoints in that controller. |
| `/service` | Create Service classes for external API connections. Use `/service Stripe` to generate a service with HTTP client, custom exceptions, and response validation. |

### Required Skill Usage

**IMPORTANT**: When creating or modifying files in these directories, you MUST use the corresponding skill. Do NOT create these files manually.

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

This ensures:
1. Consistent code patterns across the codebase
2. Proper conventions are followed (naming, structure, dependencies)
3. Related files are created together (e.g., actions with their tests)
4. DTOs and interfaces are checked/created as needed

---
name: conventions
description: Project architecture conventions and code patterns reference
argument-hint: [topic]
allowed-tools: Read, Grep, Glob
---

# Conventions Skill

Reference guide for project architecture patterns and conventions.

## Arguments

Parse `$ARGUMENTS` for optional topic filter:
- No argument: Show overview of all conventions
- `actions` or `services`: Show Action vs Service responsibilities
- `exceptions`: Show exception handling patterns
- `architecture`: Show architecture test rules

## Action vs Service Responsibilities

### Services - External API Communication ONLY

Services should ONLY handle external API communication:
- HTTP requests/responses
- Response parsing and validation
- Custom exception handling for API errors
- NO business logic
- NO database operations
- NO dependency on Actions

```php
// Service - external API only
class RebrickableService
{
    public function fetchSet(string $setNum): array
    {
        $response = Http::get("{$this->baseUrl}/sets/{$setNum}/");

        if ($response->failed()) {
            throw new RebrickableApiException($response->status());
        }

        return $response->json();
    }
}
```

### Actions - Business Logic and Orchestration

Actions handle business logic and orchestration:
- Database operations (via injected models)
- Calling services for external data
- Calling other actions for sub-operations
- Single responsibility principle

```php
// Action - orchestrates service calls and persistence
class GetSetAction
{
    public function __construct(
        private readonly RebrickableService $service,
        private readonly UpsertSetAction $upsertAction,
        private readonly Set $set,
    ) {}

    public function execute(string $setNum): Set
    {
        $existing = $this->set->newQuery()->where('set_num', $setNum)->first();

        if ($existing instanceof Set) {
            return $existing;
        }

        $data = $this->service->fetchSet($setNum);
        return $this->upsertAction->execute($data);
    }
}
```

### Example Flow

```
Controller
  └─ GetSetPartsAction (orchestration)
       ├─ RebrickableService.fetchSet() → HTTP call only
       ├─ RebrickableService.fetchSetParts() → HTTP call only
       ├─ UpsertSetAction → DB operation
       └─ StoreSetPartsAction → DB operation
```

### Avoiding Action Overlap

Before creating a new action, check for existing actions that handle similar logic:

```bash
ls app/Actions/
ls app/Actions/{Domain}/
```

When an action needs logic that another action provides, **delegate** rather than duplicate:
- `GetSetAction` delegates to `UpsertSetAction` for set creation
- `Create*Action` with update logic → rename to `Upsert*` or `Assign*`

## Exception Handling

Application exceptions are handled globally in `bootstrap/app.php`. Controllers should NOT use try-catch blocks.

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->render(fn (SetNotFoundException $e, Request $r): JsonResponse
        => response()->json(['error' => 'Set not found'], 404));

    $exceptions->render(function (RebrickableApiException $e, Request $r): JsonResponse {
        $status = $e->statusCode ?? 500;
        $message = match ($status) {
            401 => 'Invalid API key',
            default => 'Failed to fetch set data',
        };
        return response()->json(['error' => $message], $status);
    });
})
```

**Benefits**:
- Consistent error responses across the API
- Controllers stay clean (no try-catch blocks)
- Single place to update error handling logic

## Architecture Test Rules

Architecture rules are enforced via Pest tests in `tests/Architecture/`. Run with:

```bash
composer test:arch
```

### Key Rules Summary

**Controllers**:
- Must end with `Controller`
- Must return `JsonResponse` or `array` (not ResourceData directly)
- Must NOT use try-catch blocks

**Models**:
- Must extend `Illuminate\Database\Eloquent\Model`
- Must NOT have `$fillable` or `$guarded` (no mass assignment)
- Must have `@property` PHPDoc annotations

**Actions**:
- Must end with `Action`
- Only `execute` as public method

**Services**:
- Must end with `Service`
- Must NOT depend on Actions
- Must NOT use Models directly

**ResourceData**:
- Must be `readonly`, concrete classes must be `final`
- Classes accessing relationships must override `requiredRelations()`

**General**:
- All files must declare strict types
- No debugging statements (`dd`, `dump`, `var_dump`, `ray`)

**Migrations**:
- Must use anonymous classes
- Must have `void` return types
- Must NOT use cascade deletes

**Tests**:
- Must use `describe()` blocks and `it('should ...')` syntax
- Must NOT use placeholder assertions
- Unit tests must NOT use `shouldHaveReceived()` (define expectations in arrange block)
- Unit tests must NOT use `makePartial()` (use pure mocks with `getAttribute`/`setAttribute`)

For the complete list, see `tests/Architecture/*.php`.

## Model Conventions

### No Mass Assignment

Models must NOT have `$fillable` or `$guarded` properties. Always assign properties explicitly:

```php
// Correct - explicit assignment
$model = new Model();
$model->name = $data['name'];
$model->save();

// Wrong - mass assignment
$model = Model::create(['name' => $data['name']]);
```

### Property Annotations

All models must have `@property` annotations for every column:

```php
/**
 * @property positive-int $id
 * @property int $family_id
 * @property string $name
 * @property Carbon|null $created_at
 */
```

### Relationships

Use PHPDoc return type annotations:

```php
/**
 * @return BelongsTo<Family, $this>
 */
public function family(): BelongsTo
{
    return $this->belongsTo(Family::class);
}
```

## Tenant Scoping (family_id)

**Include `family_id`** for user-owned/tenant-scoped data:
- Storage-related: drawers, cabinets, shelves, storage options
- User collections: inventories, wishlists, builds
- User preferences: settings, configurations

**Exclude `family_id`** for shared/reference data:
- LEGO reference data: colors, parts, sets, themes, categories
- System data: jobs, cache, tokens

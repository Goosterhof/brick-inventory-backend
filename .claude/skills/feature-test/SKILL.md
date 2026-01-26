---
name: feature-test
description: Generate feature tests for API endpoint controllers
argument-hint: ControllerName
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(composer test, composer test:feature-coverage, composer lint, php artisan route:list)
---

# Feature Test Skill

Generate comprehensive feature tests for Laravel API controllers.

## Coverage Requirement

Feature tests must maintain **80% code coverage** for Controllers. This is enforced by CI via `composer test:feature-coverage`.

## Arguments

Parse `$ARGUMENTS` to get the Controller name (e.g., `StorageOptionController`, `FamilySetController`).

The name can be with or without the `Controller` suffix - it will be normalized.

## What This Skill Generates

Feature tests for all methods that exist in the controller, including:
- Success cases with full assertions
- Authentication tests (401 for unauthenticated) - **only for routes with auth middleware**
- Tenant isolation tests (for models with `family_id`)
- Validation tests (for store/update endpoints)

## Workflow

### Step 1: Locate and Read the Controller

1. Find the controller at `app/Http/Controllers/{ControllerName}.php`
2. If not found, inform the user and stop
3. Read the controller to identify all public methods

### Step 2: Identify Controller Methods

Extract all public methods from the controller. Common patterns:
- **CRUD**: `index`, `store`, `show`, `update`, `destroy`
- **Custom**: Any other public method (e.g., `parts`, `assignPart`, `removePart`)

Skip inherited methods from the base Controller class.

### Step 3: Detect Related Components

For each method, auto-detect:

#### Routes
Use `php artisan route:list --json` or search `routes/api.php` to find:
- HTTP method (GET, POST, PUT, PATCH, DELETE)
- Route path (e.g., `/api/storage-options`, `/api/storage-options/{storage_option}`)
- Route parameters and their names
- **Middleware**: Check if route is inside `middleware('auth:sanctum')` group
  - If protected: Generate 401 authentication tests
  - If public (no auth middleware): Skip authentication tests

#### Model
Identify the primary model from:
- Type-hinted parameters in controller methods
- Controller name (e.g., `StorageOptionController` → `StorageOption`)

#### ResourceData Class
Look for `app/Http/Resources/{ModelName}ResourceData.php` to determine JSON structure for assertions.

Read the ResourceData class constructor or `from()` method to identify expected keys. Note: ResourceData classes return directly without a `data` wrapper.

#### Form Request
For `store`/`update` methods, look for:
- `app/Http/Requests/Store{ModelName}Request.php`
- `app/Http/Requests/Update{ModelName}Request.php`
- Or subdirectory variants like `app/Http/Requests/{ModelName}/Store{ModelName}Request.php`

Read the `rules()` method to generate validation tests.

#### Tenant Scope
Check if the model has a `family_id` column by:
- Reading the model file for `@property int $family_id`
- Checking the migration

If tenant-scoped, generate family isolation tests.

### Step 4: Generate Test File

Create the test at `tests/Feature/Controllers/{ControllerName}Test.php`

## Test File Structure

```php
<?php

declare(strict_types=1);

use App\Models\{Model};
use App\Models\User;
// ... other imports
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('{ControllerName}', function (): void {
    describe('{methodName}', function (): void {
        // Success test
        it('should {expected behavior}', function (): void {
            // arrange
            // act
            // assert
        });

        // Auth test
        it('should return 401 when unauthenticated', function (): void {
            // ...
        });

        // Tenant isolation test (if applicable)
        it('should not {action} resources from another family', function (): void {
            // ...
        });

        // Validation tests (if applicable)
        it('should require {field}', function (): void {
            // ...
        });
    });
});
```

## Test Templates by Method Type

### index (GET collection)

```php
describe('index', function (): void {
    it('should return {resources} for the authenticated user family', function (): void {
        $user = User::factory()->create();
        ${resource} = {Model}::factory()->create([
            'family_id' => $user->family_id,
        ]);

        $response = $this->actingAs($user)->getJson('{route}');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', ${resource}->id);
    });

    it('should not return {resources} from other families', function (): void {
        $user = User::factory()->create();
        {Model}::factory()->create(); // Different family

        $response = $this->actingAs($user)->getJson('{route}');

        $response->assertStatus(200)
            ->assertJsonCount(0);
    });

    it('should return 401 when unauthenticated', function (): void {
        $response = $this->getJson('{route}');

        $response->assertStatus(401);
    });
});
```

### store (POST create)

```php
describe('store', function (): void {
    it('should create a {resource}', function (): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('{route}', [
            // Valid payload from Form Request rules
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('{field}', {expectedValue});

        $this->assertDatabaseHas('{table}', [
            'family_id' => $user->family_id,
            // Expected database values
        ]);
    });

    it('should return 401 when unauthenticated', function (): void {
        $response = $this->postJson('{route}', []);

        $response->assertStatus(401);
    });

    // For each required field from Form Request:
    it('should require {field}', function (): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('{route}', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['{field}']);
    });
});
```

### show (GET single)

```php
describe('show', function (): void {
    it('should return a {resource}', function (): void {
        $user = User::factory()->create();
        ${resource} = {Model}::factory()->create([
            'family_id' => $user->family_id,
        ]);

        $response = $this->actingAs($user)->getJson('{route}/' . ${resource}->id);

        $response->assertStatus(200)
            ->assertJsonPath('id', ${resource}->id);
    });

    it('should return 404 for {resource} from another family', function (): void {
        $user = User::factory()->create();
        ${resource} = {Model}::factory()->create(); // Different family

        $response = $this->actingAs($user)->getJson('{route}/' . ${resource}->id);

        $response->assertStatus(404);
    });

    it('should return 401 when unauthenticated', function (): void {
        ${resource} = {Model}::factory()->create();

        $response = $this->getJson('{route}/' . ${resource}->id);

        $response->assertStatus(401);
    });
});
```

### update (PUT/PATCH)

```php
describe('update', function (): void {
    it('should update a {resource}', function (): void {
        $user = User::factory()->create();
        ${resource} = {Model}::factory()->create([
            'family_id' => $user->family_id,
        ]);

        $response = $this->actingAs($user)->{httpMethod}Json('{route}/' . ${resource}->id, [
            // Valid payload
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('{field}', {newValue});

        $this->assertDatabaseHas('{table}', [
            'id' => ${resource}->id,
            // Updated values
        ]);
    });

    it('should return 404 for {resource} from another family', function (): void {
        $user = User::factory()->create();
        ${resource} = {Model}::factory()->create();

        $response = $this->actingAs($user)->{httpMethod}Json('{route}/' . ${resource}->id, [
            // Valid payload
        ]);

        $response->assertStatus(404);
    });

    it('should return 401 when unauthenticated', function (): void {
        ${resource} = {Model}::factory()->create();

        $response = $this->{httpMethod}Json('{route}/' . ${resource}->id, []);

        $response->assertStatus(401);
    });

    // Validation tests for required fields
});
```

### destroy (DELETE)

```php
describe('destroy', function (): void {
    it('should delete a {resource}', function (): void {
        $user = User::factory()->create();
        ${resource} = {Model}::factory()->create([
            'family_id' => $user->family_id,
        ]);

        $response = $this->actingAs($user)->deleteJson('{route}/' . ${resource}->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('{table}', ['id' => ${resource}->id]);
    });

    it('should return 404 for {resource} from another family', function (): void {
        $user = User::factory()->create();
        ${resource} = {Model}::factory()->create();

        $response = $this->actingAs($user)->deleteJson('{route}/' . ${resource}->id);

        $response->assertStatus(404);
        $this->assertDatabaseHas('{table}', ['id' => ${resource}->id]);
    });

    it('should return 401 when unauthenticated', function (): void {
        ${resource} = {Model}::factory()->create();

        $response = $this->deleteJson('{route}/' . ${resource}->id);

        $response->assertStatus(401);
    });
});
```

### Custom Methods (Non-CRUD)

For custom methods, analyze:
1. HTTP method and route from `routes/api.php`
2. Method signature for parameters
3. Return type for assertions

Generate tests following the same pattern:
- Success case
- Auth test
- Tenant isolation (if applicable)
- Validation tests (if Form Request used)

## Nested Routes

For nested routes like `/api/storage-options/{storage_option}/parts`:

1. Identify the parent resource from the route
2. Create parent resource belonging to user's family
3. Test that child operations respect parent's family scope

Example:
```php
describe('parts', function (): void {
    it('should return parts for a storage option', function (): void {
        $user = User::factory()->create();
        $storageOption = StorageOption::factory()->create([
            'family_id' => $user->family_id,
        ]);
        $part = Part::factory()->create();
        StorageOptionPart::factory()->create([
            'storage_option_id' => $storageOption->id,
            'part_id' => $part->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/storage-options/{$storageOption->id}/parts");

        $response->assertStatus(200)
            ->assertJsonCount(1);
    });

    it('should return 404 for storage option from another family', function (): void {
        $user = User::factory()->create();
        $storageOption = StorageOption::factory()->create(); // Different family

        $response = $this->actingAs($user)
            ->getJson("/api/storage-options/{$storageOption->id}/parts");

        $response->assertStatus(404);
    });
});
```

## Validation Test Generation

Read the Form Request's `rules()` method and generate tests for:

### Required Fields
For rules containing `'required'`:
```php
it('should require {field}', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('{route}', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['{field}']);
});
```

### Enum Validation
For rules using `Rule::enum()`:
```php
it('should validate {field} enum', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('{route}', [
        '{field}' => 'invalid_value',
        // ... other required fields
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['{field}']);
});
```

### Type Validation
For rules like `'integer'`, `'string'`, `'date'`:
```php
it('should validate {field} is {type}', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('{route}', [
        '{field}' => {invalidValue}, // e.g., 'not-a-number' for integer
        // ... other required fields
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['{field}']);
});
```

## JSON Structure Assertions

Read the ResourceData class to determine expected keys. This codebase uses `ResourceData` classes that return directly without a `data` wrapper.

For a ResourceData returning:
```php
public static function from(Model $model): self
{
    return new self(
        id: $model->id,
        name: $model->name,
        created_at: $model->created_at,
    );
}
```

Generate assertions for single resources:
```php
$response->assertStatus(200)
    ->assertJsonStructure([
        'id',
        'name',
        'created_at',
    ]);
```

For collections (returned as arrays without wrapper):
```php
$response->assertStatus(200)
    ->assertJsonStructure([
        '*' => [
            'id',
            'name',
            'created_at',
        ],
    ]);
```

Use `assertJsonPath` for specific value assertions:
```php
$response->assertStatus(200)
    ->assertJsonPath('name', 'Expected Name')
    ->assertJsonPath('0.name', 'First Item Name'); // For collections
```

## Factory Dependencies

If a factory doesn't exist, inform the user:

> "Factory `{Model}Factory` not found. Please create it using the `/factory` skill first, then run this skill again."

## After Generation

1. Run `composer lint` to format the code
2. Run `composer test:feature-coverage` to verify 80% coverage is maintained
3. Report the created test file path to the user
4. Suggest running `composer test` to verify the tests pass

## Important Notes

- Always use `declare(strict_types=1);`
- Use `RefreshDatabase` trait via `uses(RefreshDatabase::class);`
- Use `actingAs($user)` for authenticated requests
- Use descriptive `it('should ...')` test names
- Include `// arrange`, `// act`, `// assert` comments for clarity (optional but recommended)
- For delete operations, always use `assertDatabaseMissing` (hard delete assumed)
- Routes should use the actual route paths from `routes/api.php`, not guessed paths
- **Only generate 401 tests for routes protected by `auth:sanctum` middleware** - check `routes/api.php` to determine if a route is inside the `middleware('auth:sanctum')` group

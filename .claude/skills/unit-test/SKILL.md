---
name: unit-test
description: Create or run unit tests for this Laravel project using Pest PHP
argument-hint: [ClassName|--run]
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(composer test:*)
---

# Unit Test Skill

You are helping with unit tests for a Laravel project using Pest PHP.

## Arguments

Parse `$ARGUMENTS` to determine the action:

- **No arguments or `--run`**: Run all unit tests with `composer test`
- **`--run ClassName`**: Run tests for a specific class
- **`ClassName`** (e.g., `CreateSetAction`, `UserResource`): Generate unit tests for that class

## Project Conventions

Follow these testing conventions strictly:

### File Structure
- Unit tests go in `tests/Unit/`
- Action tests: `tests/Unit/Actions/{ActionName}Test.php`
- Resource tests: `tests/Unit/Resources/{ResourceName}Test.php`
- Service tests: `tests/Unit/Services/{ServiceName}Test.php`
- Nested namespaces mirror the app structure (e.g., `App\Actions\Auth\` → `tests/Unit/Actions/Auth/`)

### Test Syntax
```php
<?php

declare(strict_types=1);

use App\Actions\SomeAction;
// ... other imports

describe('SomeAction', function (): void {
    it('should do something specific', function (): void {
        // arrange

        // act

        // assert with expect()
    });
});
```

### Key Rules
1. Always use `declare(strict_types=1);`
2. Use `describe` blocks with the class name
3. Use `it('should ...')` syntax for test cases
4. Use `expect()` assertions, NOT `$this->assert*()`
5. Include `// arrange`, `// act`, `// assert` comments
6. Unit tests must NOT touch the database - use Mockery for dependencies
7. Inject dependencies via constructor and mock them

### Mocking Pattern
For classes with dependencies, inject them and use Mockery:

```php
$mockDependency = Mockery::mock(SomeDependency::class);
$mockDependency->shouldReceive('method')
    ->with($expectedArgs)
    ->once()
    ->andReturn($expectedResult);

$action = new SomeAction($mockDependency);
$result = $action->execute($input);

expect($result)->toBe($expectedResult);
```

### Mockery Assertion Style

**Always define expectations in the arrange block**, not after the act:

```php
// ✅ CORRECT - expectations in arrange block
it('should not call service when data is empty', function (): void {
    // arrange
    $service = Mockery::mock(SomeService::class);
    $service->shouldReceive('process')->never();  // ← Expectation set upfront

    $action = new SomeAction($service);

    // act
    $action->execute([]);

    // assert - Mockery verifies expectations automatically
});

// ❌ WRONG - using shouldNotHaveReceived in assert block
it('should not call service when data is empty', function (): void {
    // arrange
    $service = Mockery::mock(SomeService::class);
    $action = new SomeAction($service);

    // act
    $action->execute([]);

    // assert
    $service->shouldNotHaveReceived('process');  // ← Avoid this pattern
});
```

**Rationale:** Defining expectations upfront keeps all mock setup in the arrange block, and Mockery automatically verifies at test teardown.

### No Placeholder Assertions

When Mockery expectations are the only verification needed, don't add placeholder assertions:

```php
// ✅ CORRECT - Mockery expectations are sufficient
it('should save the model', function (): void {
    // arrange
    $model = Mockery::mock(Model::class);
    $model->shouldReceive('save')->once();

    // act
    $action->execute($model);

    // assert - Mockery expectations verify save() was called
});

// ❌ WRONG - unnecessary placeholder assertion
it('should save the model', function (): void {
    // arrange
    $model = Mockery::mock(Model::class);
    $model->shouldReceive('save')->once();

    // act
    $action->execute($model);

    // assert
    expect(true)->toBeTrue();  // ← Don't do this
});
```

### Pure Mocks (Recommended - Faster)

Avoid `makePartial()` which instantiates real Eloquent models with boot logic. Instead, mock `getAttribute` and `setAttribute` directly.

**Only mock attributes actually accessed by the code under test.** Before adding `getAttribute` or `setAttribute` mocks, verify the Action actually reads or writes that attribute. Common mistakes:
- Mocking `getAttribute('id')` when the Action never reads `id`
- Mocking `getAttribute('name')` when the Action only *sets* `name` (use `setAttribute` mock instead)
- Mocking `getAttribute('exists')` out of habit - only mock if the Action checks existence

```php
$savedValue = null;
$model = Mockery::mock(Model::class);

// Mock property reads via getAttribute
$model->allows('getAttribute')->with('name')->andReturn('John');
$model->allows('getAttribute')->with('email')->andReturn('john@example.com');

// Mock property writes via setAttribute - capture values for assertions
$model->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$savedValue): void {
    if ($key === 'status') {
        $savedValue = $value;
    }
});

// Mock methods
$model->shouldReceive('save')->once();

$action = new SomeAction();
$action->execute($model);

expect($savedValue)->toBe('active');
```

**Important:** For mutable state tracking, use full closures with `use (&$var)` reference capture. Arrow functions (`fn() => $var`) capture by **value** and won't see updates:

```php
// WRONG - arrow function captures by value
$model->allows('getAttribute')->with('location')->andReturnUsing(fn () => $savedLocation);

// CORRECT - full closure captures by reference
$model->allows('getAttribute')->with('location')->andReturnUsing(function () use (&$savedLocation) {
    return $savedLocation;
});
```

### For Eloquent Models in Actions
If an action uses Eloquent models, they should be injected and mocked:

```php
// Track values set by the action
$savedValues = [];
$modelInstance = Mockery::mock(Model::class);
$modelInstance->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$savedValues): void {
    $savedValues[$key] = $value;
});
$modelInstance->allows('getAttribute')->andReturnUsing(function ($key) use (&$savedValues): mixed {
    return $savedValues[$key] ?? null;
});
$modelInstance->shouldReceive('save')->once();

// The injected model mock returns the instance
$model = Mockery::mock(Model::class);
$model->shouldReceive('newInstance')
    ->withNoArgs()
    ->andReturn($modelInstance);

$action = new SomeAction($model);
$action->execute($data);

// Assert properties were set correctly
expect($savedValues['name'])->toBe('expected value');
```

### For Services with HTTP Calls
Services that make external API calls should use Laravel's `Http::fake()` to mock responses:

```php
use Illuminate\Support\Facades\Http;

it('should fetch data from API', function (): void {
    // arrange
    Http::fake([
        'https://api.example.com/endpoint' => Http::response([
            'id' => 1,
            'name' => 'Test',
        ]),
    ]);

    $service = new SomeService('api-key', 'https://api.example.com');

    // act
    $result = $service->fetchData();

    // assert
    expect($result['name'])->toBe('Test');

    // Always verify HTTP requests comprehensively
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.example.com/endpoint'
        && $request->method() === 'GET'
        && $request->header('Authorization') === ['Bearer api-key']);
});

it('should throw exception on API failure', function (): void {
    Http::fake([
        'https://api.example.com/endpoint' => Http::response([], 500),
    ]);

    $service = new SomeService('api-key', 'https://api.example.com');

    expect(fn (): array => $service->fetchData())->toThrow(RequestException::class);
});
```

**HTTP Assertion Best Practices:**
- Always verify the URL, HTTP method, and headers (especially Authorization)
- Use `Http::assertSentCount()` when testing pagination or multiple requests
- Verify each request in multi-request scenarios has proper authentication

### For Services with Database Queries
Services that query the database should inject models and use `newQuery()` pattern:

```php
// Mock the query builder chain
$queryBuilder = Mockery::mock(Builder::class);
$queryBuilder->shouldReceive('where')->with('field', 'value')->andReturnSelf();
$queryBuilder->shouldReceive('first')->andReturn($existingModel);

// Mock the model to return the query builder
$model = Mockery::mock(Model::class);
$model->shouldReceive('newQuery')->andReturn($queryBuilder);

$service = new SomeService($model);
```

For services that both query AND create records:

```php
// Query returns null (record doesn't exist)
$queryBuilder = Mockery::mock(Builder::class);
$queryBuilder->shouldReceive('where')->andReturnSelf();
$queryBuilder->shouldReceive('first')->andReturn(null);

// New instance will be created - track values set by the service
$savedValues = [];
$newInstance = Mockery::mock(Model::class);
$newInstance->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$savedValues): void {
    $savedValues[$key] = $value;
});
$newInstance->allows('getAttribute')->andReturnUsing(function ($key) use (&$savedValues): mixed {
    return $savedValues[$key] ?? null;
});
$newInstance->shouldReceive('save')->once();

$model = Mockery::mock(Model::class);
$model->shouldReceive('newQuery')->andReturn($queryBuilder);
$model->shouldReceive('newInstance')->andReturn($newInstance);

$service = new SomeService($model);
```

## When Generating Tests

1. First, read the source file to understand its structure
2. Identify all public methods and their behavior
3. Create tests for:
   - Happy path (normal operation)
   - Edge cases
   - Different input scenarios
4. Mock all external dependencies
5. Ensure tests are isolated and repeatable

## When Running Tests

- Use `composer test` to run all tests
- Use `./vendor/bin/pest tests/Unit/Path/To/TestFile.php` to run specific tests
- Use `composer test:coverage` to run unit tests with 100% coverage requirement (Actions & Services only)
- After running, report the results clearly

## Coverage Requirements

Actions and Services require 100% unit test coverage. The CI pipeline enforces this via `composer test:coverage` which:
- Runs only unit tests
- Uses PCOV for coverage
- Targets only `app/Actions` and `app/Services` directories
- Fails if coverage drops below 100%

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

### For Eloquent Models in Actions
If an action uses Eloquent models, they should be injected and mocked. Use `makePartial()` on instance mocks to allow property assignment:

```php
// The instance mock needs makePartial() to allow property setting
$modelInstance = Mockery::mock(Model::class)->makePartial();
$modelInstance->shouldReceive('save')->once();

// The injected model mock returns the instance
$model = Mockery::mock(Model::class);
$model->shouldReceive('newInstance')
    ->withNoArgs()
    ->andReturn($modelInstance);

$action = new SomeAction($model);
$action->execute($data);

// Assert properties were set correctly
expect($modelInstance->name)->toBe('expected value');
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
- Use `composer test -- --filter=ClassName` to run specific tests
- After running, report the results clearly

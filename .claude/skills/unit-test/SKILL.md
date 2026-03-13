---
name: unit-test
description: Create or run unit tests for this Laravel project using Pest PHP
argument-hint: [ClassName|--run]
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(composer test:*)
---

# Unit Test Skill

Parse `$ARGUMENTS`: no args/`--run` = run tests, `ClassName` = generate tests.

**Coverage requirement: 100%** for Actions & Services (`composer test:coverage`).

## Test Structure

Tests at `tests/Unit/{Actions,Services,Resources}/` mirroring app structure. Read existing unit tests for the template pattern.

```php
describe('ClassName', function (): void {
    it('should do thing', function (): void {
        // arrange
        // act
        // assert with expect()
    });
});
```

## Mockery Rules (Non-Obvious)

### Expectations in Arrange, Not Assert
```php
// CORRECT — set expectations upfront, Mockery verifies at teardown
$service->shouldReceive('process')->never();
$action->execute([]);

// WRONG — avoid shouldHaveReceived/shouldNotHaveReceived
$action->execute([]);
$service->shouldNotHaveReceived('process');
```

### No Placeholder Assertions
When Mockery expectations are the only verification, do NOT add `expect(true)->toBeTrue()`.

### Pure Mocks — No makePartial()
Mock `getAttribute`/`setAttribute` instead of using `makePartial()` (avoids Eloquent boot overhead):

```php
$savedValues = [];
$model = Mockery::mock(Model::class);
$model->allows('setAttribute')->andReturnUsing(function ($key, $value) use (&$savedValues): void {
    $savedValues[$key] = $value;
});
$model->allows('getAttribute')->andReturnUsing(function ($key) use (&$savedValues): mixed {
    return $savedValues[$key] ?? null;
});
$model->shouldReceive('save')->once();
```

**Only mock attributes the code actually reads/writes.** Don't mock `getAttribute('id')` if the action never reads `id`.

### Closure Capture — Full Closure, Not Arrow
```php
// WRONG — arrow fn captures by value
$model->allows('getAttribute')->with('x')->andReturnUsing(fn () => $saved);

// CORRECT — full closure captures by reference
$model->allows('getAttribute')->with('x')->andReturnUsing(function () use (&$saved) {
    return $saved;
});
```

### Services: Use Http::fake()
```php
Http::fake([
    'https://api.example.com/endpoint' => Http::response(['id' => 1]),
]);
$service = new ExampleService('key', 'https://api.example.com');
// Always verify URL, method, and headers with Http::assertSent()
```

## Running Tests

- All: `composer test`
- Specific file: `./vendor/bin/pest tests/Unit/Path/To/TestFile.php`
- With coverage: `composer test:coverage`

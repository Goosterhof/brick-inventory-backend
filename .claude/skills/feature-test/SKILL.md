---
name: feature-test
description: Generate feature tests for API endpoint controllers
argument-hint: ControllerName
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(composer test, composer test:feature-coverage, composer lint, php artisan route:list)
---

# Feature Test Skill

Generate feature tests for controllers. Parse `$ARGUMENTS` for the controller name.

**Coverage requirement: 80%** for Controllers (`composer test:feature-coverage`).

## Workflow

1. Read the controller to identify public methods
2. Find routes in `routes/api.php` — get HTTP methods, paths, middleware
3. Read ResourceData class for JSON structure assertions
4. Read Form Requests for validation rules
5. Check if model has `family_id` for tenant isolation tests
6. Generate test at `tests/Feature/Controllers/{ControllerName}Test.php`
7. Run `composer lint` then `composer test:feature-coverage`

Read existing feature tests for the template pattern.

## Test Structure

```php
uses(RefreshDatabase::class);

describe('ControllerName', function (): void {
    describe('methodName', function (): void {
        it('should ...', function (): void { });
    });
});
```

## What to Generate Per Method

| Test | When |
|------|------|
| Success case | Always |
| 401 unauthenticated | Only if route has `auth:sanctum` middleware |
| 404 tenant isolation | If model has `family_id` |
| 422 validation (per required field) | If method uses a Form Request |

## Key Conventions

- Use `$this->actingAs($user)` (not `Sanctum::actingAs()`)
- ResourceData returns without `data` wrapper — assert at root level
- For collections: `assertJsonCount(1)` + `assertJsonPath('0.id', ...)`
- For creates: `assertStatus(201)` + `assertDatabaseHas`
- For deletes: `assertStatus(204)` + `assertDatabaseMissing`
- Tenant isolation: create resource with different family, expect 404
- Nested routes: create parent belonging to user's family first

## Factory Dependencies

If a factory doesn't exist, stop and tell user to run `/factory` first.

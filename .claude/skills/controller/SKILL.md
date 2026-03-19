---
name: controller
description: Create a resource controller for a Laravel model with CRUD operations
argument-hint: ModelName
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(composer lint)
---

# Controller Skill

Create resource controllers. Parse `$ARGUMENTS` for the model name.

## Dependencies Check

Before creating, verify these exist (invoke their skills if missing):
- `app/Http/Resources/{ModelName}ResourceData.php` → `/resource-data`
- `app/Http/Requests/{Domain}/Store{ModelName}Request.php` → `/form-request`
- `app/Http/Requests/{Domain}/Update{ModelName}Request.php` → `/form-request`

## Key Rules

- Read existing controllers for the template pattern
- Actions injected via **method parameters**, not constructor
- Return `JsonResponse` or `array` — never return ResourceData directly
- Use `->toResponseWithStatus(201)` for created responses
- No try-catch blocks — exceptions handled globally in `bootstrap/app.php`
- No `family_id` checks — handled by middleware and actions
- Use `#[CurrentUser]` attribute for authenticated user

## Route Registration

Add to `routes/api.php` inside the `auth:sanctum` middleware group. Use explicit routes (NOT `apiResource`):

| Convention | Format | Example |
|------------|--------|---------|
| Route path | kebab-case plural | `storage-options` |
| Route parameter | snake_case singular | `storage_option` |

Use `->scopeBindings()` for parent-child nested routes.

## After Creation

1. Run `composer lint`
2. Tell user which Actions need creating via `/action`

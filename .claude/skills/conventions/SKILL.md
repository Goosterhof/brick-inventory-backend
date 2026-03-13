---
name: conventions
description: Project architecture conventions and code patterns reference
argument-hint: [topic]
allowed-tools: Read, Grep, Glob
---

# Conventions

Reference guide. Parse `$ARGUMENTS` for optional topic: `actions`, `services`, `exceptions`, `architecture`.

## Domain Convention

`{Domain}` = subdirectory organizing related files by primary model:
- `app/Actions/{Domain}/`, `app/Contracts/{Domain}/`, `app/Http/Requests/{Domain}/`

## Action vs Service Responsibilities

**Services** — External HTTP only. No business logic, no DB, no Action dependencies.

**Actions** — Business logic and orchestration. DB operations, calling services, calling other actions.

```
Controller
  └─ GetSetPartsAction (orchestration)
       ├─ RebrickableService.fetchSet() → HTTP only
       ├─ RebrickableService.fetchSetParts() → HTTP only
       ├─ UpsertSetAction → DB operation
       └─ StoreSetPartsAction → DB operation
```

**Delegation over duplication** — when an action needs another action's logic, inject and call it.

## Exception Handling

Handled globally in `bootstrap/app.php`. Controllers must NOT use try-catch.

## Architecture Test Rules

Run: `composer test:arch`. See `tests/Architecture/*.php` for the full list.

| Layer | Key Rules |
|-------|-----------|
| Controllers | Return `JsonResponse` or `array`, no try-catch, no `Gate`/`->authorize()` |
| Models | No `$fillable`/`$guarded`, must have `@property` PHPDoc |
| Actions | Only `execute` as public method |
| Services | Must NOT depend on Actions or Models |
| ResourceData | `readonly`, concrete classes `final`, override `requiredRelations()` if accessing relations |
| Migrations | Anonymous classes, `void` return types, NO cascade deletes |
| Tests | `describe()` + `it('should ...')`, no placeholder assertions, no `shouldHaveReceived()`, no `makePartial()` |
| All | `declare(strict_types=1)`, no `dd`/`dump`/`var_dump`/`ray` |

## Model Conventions

- No mass assignment — assign properties explicitly, then `->save()`
- `@property` annotations for every column
- Relationship methods with PHPDoc: `@return BelongsTo<Family, $this>`

## Tenant Scoping (family_id)

**Include** for user-owned data: storage, inventories, preferences, builds.
**Exclude** for shared reference data: colors, parts, sets, themes, system tables.

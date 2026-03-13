# ADR-0004: Explicit Cascade Deletion, Not Database-Level

**Status:** Accepted

## Context

When a parent record is deleted, child records need cleanup. Laravel supports both database-level cascade deletes and application-level deletion logic.

## Decision

**No cascade deletes in migrations.** Never use `->onDelete('cascade')` or `->cascadeOnDelete()`. Handle deletion in Action classes via the `cascadeRelations()` model method.

Every model declares its cascade relationships:
```php
public static function cascadeRelations(): array
{
    return ['children', 'parts'];
}
```

Delete Actions must handle all declared relations before deleting the parent.

## Alternatives Considered

- **Database-level cascades** — silent, invisible, impossible to hook into for auditing, soft deletes, or custom cleanup logic. A single `DELETE` can wipe related data without any application awareness.

## Consequences

- Models must declare `cascadeRelations()` listing all HasMany/HasOne relationships
- Delete Actions must explicitly handle each relation
- More code, but no surprise deletions

## Enforced By

- `tests/Architecture/MigrationArchitectureTest.php` — no `onDelete('cascade')`, no `cascadeOnDelete()`
- `tests/Architecture/CascadeRelationArchitectureTest.php` — validates `cascadeRelations()` exists, lists all relations, and Delete Actions handle them

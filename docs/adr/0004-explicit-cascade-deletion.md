# Decision: Explicit Cascade Deletion, Not Database-Level

**Date**: 2026-03-22
**Feature**: Safe deletion of parent records with dependent children
**Status**: accepted
**Transferability**: universal

## Context

When a parent record is deleted, child records need cleanup. Laravel supports both database-level cascade deletes (`onDelete('cascade')`) and application-level deletion logic. Database cascades are silent — a single `DELETE` wipes related data without the application knowing, bypassing any hooks, auditing, soft deletes, or custom cleanup.

The forces:
- Deletion must be visible and auditable at the application level
- Custom cleanup logic (e.g., recursive deletion of nested storage options) can't hook into database cascades
- If soft deletes are ever added, database cascades won't cascade the soft delete flag
- Missing cascade handling must be caught by automated tests, not production incidents

## Options Considered

| Option | Pros | Cons | Why eliminated / Why chosen |
|--------|------|------|-----------------------------|
| **Explicit cascade in Actions with `cascadeRelations()` contract** | Visible; auditable; supports custom logic; testable | More code; requires discipline to maintain cascade lists | **Chosen** — visibility and testability outweigh verbosity |
| **Database-level `onDelete('cascade')`** | Zero application code; handled by PostgreSQL | Silent; invisible to application; can't hook for auditing or soft deletes; a single raw DELETE wipes everything | Eliminated — too dangerous for data integrity |
| **Eloquent model events (`deleting` listener)** | Keeps logic on the model | Event listeners are implicit; easy to miss; order-dependent; don't compose well for recursive deletion | Eliminated — implicit behavior in a codebase that values explicitness |
| **Soft deletes everywhere** | Nothing actually deleted | Doesn't address the cascade problem — soft-deleted parents with hard-referenced children create orphans | Eliminated — orthogonal to the cascade question |

## Decision

**No cascade deletes in migrations.** Never use `->onDelete('cascade')` or `->cascadeOnDelete()`. Handle deletion in Action classes via the `cascadeRelations()` model method.

Every model declares its cascade relationships:
```php
public static function cascadeRelations(): array
{
    return ['children', 'storageOptionParts'];
}
```

Delete Actions must handle all declared relations before deleting the parent:
```php
final readonly class DeleteStorageOptionAction
{
    public function execute(StorageOption $storageOption): void
    {
        $storageOption->load('children.storageOptionParts', 'storageOptionParts');

        $this->connection->transaction(function () use ($storageOption): void {
            $this->deleteRecursive($storageOption);
        });
    }

    private function deleteRecursive(StorageOption $storageOption): void
    {
        foreach ($storageOption->children as $child) {
            $this->deleteRecursive($child);
        }
        $storageOption->storageOptionParts()->delete();
        $storageOption->delete();
    }
}
```

See also ADR-0010, which documents the `cascadeRelations()` method contract in detail.

## Consequences

- Models must declare `cascadeRelations()` listing all HasMany/HasOne relationships
- Delete Actions must explicitly handle each relation — the architecture test cross-references
- More code, but no surprise deletions — every cascade path is visible in version control
- Adding a new HasMany relationship without updating `cascadeRelations()` fails the architecture test immediately

## Enforcement

| What | Mechanism | Scope |
|------|-----------|-------|
| No `onDelete('cascade')` in migrations | `MigrationArchitectureTest` | `database/migrations/` |
| No `cascadeOnDelete()` in migrations | `MigrationArchitectureTest` | `database/migrations/` |
| `cascadeRelations()` exists on all models | `CascadeRelationArchitectureTest` | `app/Models/` |
| Delete Actions handle all declared relations | `CascadeRelationArchitectureTest` | `app/Actions/` |

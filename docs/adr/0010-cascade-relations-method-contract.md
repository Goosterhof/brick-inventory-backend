# Decision: cascadeRelations() Method Contract

**Date**: 2026-03-22
**Feature**: Explicit cascade deletion machinery (extends ADR-0004)
**Status**: accepted
**Transferability**: project-specific

## Context

ADR-0004 established that cascade deletes must be explicit in Actions, not database-level `onDelete('cascade')`. But that decision left a gap: _how_ does a delete Action know which relationships to cascade? Without a formal contract, each delete Action had to inspect the model's relationships manually, and nothing prevented a developer from adding a new `HasMany` relationship without updating the corresponding delete logic.

The forces:
- Delete Actions need a reliable manifest of what to cascade
- New relationships added to models must be surfaced to delete logic immediately
- The contract must be enforceable by automated tests, not code review memory

## Options Considered

| Option | Pros | Cons | Why eliminated / Why chosen |
|--------|------|------|-----------------------------|
| **Static `cascadeRelations()` method on every model** | Declarative, testable, co-located with the model's relationships | Every model must implement it, even those with no cascades (returns `[]`) | **Chosen** — the empty-array cost is trivial; the safety guarantee is not |
| **A trait with auto-discovery via reflection** | No manual declaration needed | Fragile — relies on return type inspection, breaks with dynamic relationships, harder to override | Eliminated — magic over explicitness |
| **Annotations/attributes on relationship methods** | Co-located with each relationship | No single source of truth; easy to miss one; harder to test as a unit | Eliminated — scattered declaration |
| **Convention: delete Action authors just know** | Zero ceremony | Zero safety; will break the moment someone adds a relationship and forgets the delete path | Eliminated — the failure this pattern exists to prevent |

## Decision

Every Eloquent model declares a `public static function cascadeRelations(): array` that returns the names of relationships requiring cascade deletion. Only `HasMany` and `HasOne` relationships may appear. Architecture tests enforce three invariants:

1. **Every model has the method** — no model can opt out of declaring its cascade surface
2. **Every HasMany/HasOne is declared** — adding a relationship without declaring it in `cascadeRelations()` fails the test
3. **Every delete Action handles all declared relations** — the Action must reference each relation from the model's cascade list

```php
// Model declaration
public static function cascadeRelations(): array
{
    return ['children', 'storageOptionParts'];
}

// Delete Action must handle both 'children' and 'storageOptionParts'
```

Models with no cascade relationships return an empty array. This is intentional — it forces the developer to actively consider the question.

## Consequences

- Every new `HasMany`/`HasOne` relationship immediately triggers a test failure until declared
- Delete Actions cannot silently skip relationships — the test cross-references the model's declaration
- Models are slightly more verbose (one method, even when returning `[]`)
- The pattern is self-documenting: reading `cascadeRelations()` tells you exactly what gets deleted

## Enforcement

| What | Mechanism | Scope |
|------|-----------|-------|
| All models declare `cascadeRelations()` | `CascadeRelationArchitectureTest` | `app/Models/` |
| Only HasMany/HasOne in cascade list | `CascadeRelationArchitectureTest` | `app/Models/` |
| Every HasMany/HasOne is declared | `CascadeRelationArchitectureTest` | `app/Models/` |
| Delete Actions handle all declared relations | `CascadeRelationArchitectureTest` | `app/Actions/` |
| No database-level cascade (ADR-0004) | `MigrationArchitectureTest` | `database/migrations/` |

## Open Questions

- Should `BelongsToMany` (pivot) relationships ever appear in `cascadeRelations()`? Currently excluded — pivot cleanup is handled differently. If a future model needs it, this decision may need revisiting.

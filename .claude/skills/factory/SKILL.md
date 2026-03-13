---
name: factory
description: Generate a factory from an existing model
argument-hint: ModelName
allowed-tools: Read, Grep, Glob, Write, Bash(composer lint, composer phpstan, composer test:arch)
---

# Factory Skill

Generate a factory from an existing model. Parse `$ARGUMENTS` for the model name.

## Workflow

1. Find model in `app/Models/` — stop if missing
2. Parse `@property` PHPDoc and relationships from model
3. Find migration for constraints (unique, nullable, defaults)
4. Generate factory at `database/factories/` (mirror model namespace structure)
5. Run `composer lint`, `composer phpstan`, `composer test:arch`

Read existing factories for the template pattern.

## Foreign Key Detection Priority (CRITICAL)

A column is a **foreign key** if the migration uses `foreignId()`/`->constrained()` OR the model has a `BelongsTo` for it.

| Type | Example | Faker |
|------|---------|-------|
| Required FK | `family_id` with `BelongsTo` | `Family::factory()` |
| Nullable FK | `parent_id` nullable with `BelongsTo` | `null` |
| External ID (no relationship) | `rebrickable_id` | `fake()->unique()->randomNumber(4)` |

**If `*_id` has no constraint and no relationship, it's an external identifier — not a FK.**

## State Methods

| Relationship Type | Method Name | Notes |
|-------------------|-------------|-------|
| Required FK | `for{Relation}(Model $m)` | Sets the FK |
| Nullable/optional | `with{Relation}(Model $m)` | Sets the FK |
| Self-referential | `withParent(Model $p)` | Also copies `family_id` if tenant-scoped |

## Password Fields

Use static caching:
```php
protected static ?string $password;

// In definition():
'password' => static::$password ??= Hash::make('password'),
```

## Overwrite Handling

If factory already exists, **ask user for confirmation** before overwriting.

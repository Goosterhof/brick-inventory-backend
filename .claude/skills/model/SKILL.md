---
name: model
description: Generate an Eloquent model from an existing migration
argument-hint: ModelName
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(php artisan)
---

# Model Skill

Generate a model from its migration. Parse `$ARGUMENTS` for the model name.

## Workflow

1. Find migration (`create_{table}_table`) — stop if missing
2. Parse columns, types, nullable, foreign keys
3. Infer relationships (`foreignId` → `BelongsTo`, check other models for inverse)
4. Generate at `app/Models/{ModelName}.php`
5. Run `composer lint` then `composer phpstan`

Read existing models for the template pattern.

## Type Mapping

| Migration | PHPDoc |
|-----------|--------|
| `id()` | `positive-int` |
| `string()`, `text()` | `string` |
| `integer()`, `unsignedInteger()` | `int` |
| `boolean()` | `bool` |
| `decimal()`, `float()` | `float` |
| `date()`, `dateTime()`, `timestamp()` | `Carbon` |
| `json()` | `array` |
| `foreignId()` | `int` |
| `timestamps()` | `Carbon\|null $created_at`, `Carbon\|null $updated_at` |

Nullable columns: append `\|null`.

## Key Rules

- No `$fillable` or `$guarded` (exception: `protected $guarded = ['password']` for User)
- `@property` annotations for ALL columns
- Relationship PHPDoc: `@return BelongsTo<Family, $this>`
- `casts()` method for enums, non-timestamp dates, booleans, arrays
- `/** @use HasFactory<{ModelName}Factory> */` above `use HasFactory;`

---
name: resource-data
description: Create a ResourceData class for API responses based on Eloquent models
argument-hint: [ModelName]
allowed-tools: Read, Grep, Glob, Write, Edit
---

# ResourceData Skill

You are helping create ResourceData classes for API responses in this Laravel project.

## Arguments

Parse `$ARGUMENTS` to determine the model:

- **`ModelName`** (e.g., `Part`, `FamilySet`, `StorageOption`): Generate a ResourceData class for that model

## What is ResourceData?

ResourceData classes are DTO-style classes that transform Eloquent models into API responses. They extend the base `App\Http\Resources\ResourceData` class and provide:

- Type-safe properties with snake_case naming
- Automatic JSON serialization via reflection
- Static `from(Model)` factory method
- Static `collection(Collection)` method for lists

## Project Conventions

### File Location
- ResourceData classes go in `app/Http/Resources/`
- Naming: `{ModelName}ResourceData.php` (e.g., `PartResourceData.php`)

### Class Structure

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ModelName;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ResourceData<ModelName>
 */
final readonly class ModelNameResourceData extends ResourceData
{
    public function __construct(
        public int $id,
        public string $some_property,
        public ?string $nullable_property,
    ) {}

    public static function from(Model $model): static
    {
        return new self(
            id: $model->id,
            some_property: $model->some_property,
            nullable_property: $model->nullable_property,
        );
    }
}
```

### Key Rules

1. **Always use `final readonly class`**
2. **Properties must be snake_case** (matching the JSON output)
3. **Use the `@extends ResourceData<ModelName>` docblock** for generic type info
4. **No `@var` docblocks in `from()` method** - the generic handles typing
5. **Relations must be loaded before passing to the resource** - no lazy loading

### Handling Nested Resources

When a model has relations that should be included:

```php
final readonly class FamilySetResourceData extends ResourceData
{
    public function __construct(
        public int $id,
        public int $quantity,
        public SetResourceData $set,  // Nested resource
    ) {}

    public static function from(Model $model): static
    {
        return new self(
            id: $model->id,
            quantity: $model->quantity,
            set: SetResourceData::from($model->set),  // Relation must be loaded
        );
    }
}
```

### Handling Nullable Relations

```php
public ?ColorResourceData $color,

// In from():
color: $model->color !== null
    ? ColorResourceData::from($model->color)
    : null,
```

### Handling Arrays of Nested Resources

```php
/**
 * @param array<int, ChildResourceData> $children
 */
public function __construct(
    public int $id,
    public array $children,
) {}

// In from():
children: array_map(
    self::from(...),
    $model->children->all(),
),
```

### Common Property Types

| Model Type | ResourceData Type |
|------------|-------------------|
| `int` | `int` |
| `string` | `string` |
| `bool` | `bool` |
| `?string` | `?string` |
| `Carbon` | `?Carbon` (formatted by base class as ISO8601) |
| `BackedEnum` | `EnumClass` (converted to value by base class) |
| `BelongsTo` relation | `RelatedResourceData` |
| `HasMany` relation | `array` with docblock |

### Date Formatting

The base class formats `DateTimeInterface` as ISO8601 (`'c'` format). For custom formats:

```php
public ?string $purchase_date,  // Store as formatted string

// In from():
purchase_date: $model->purchase_date?->format('Y-m-d'),
```

## Usage in Controllers

### Single Resource
```php
return PartResourceData::from($part);
```

### Collection
```php
return PartResourceData::collection($parts);
```

### With Status Code
```php
return PartResourceData::from($part)->toResponseWithStatus(201);
```

## Relationship Loading Pattern

ResourceData classes are responsible for loading their own relationships. This keeps relationship knowledge centralized and avoids N+1 queries.

**Pattern**: Override `requiredRelations()` to declare needed relationships:

```php
final readonly class FamilySetResourceData extends ResourceData
{
    protected static function requiredRelations(): array
    {
        return ['set'];  // Relationships this ResourceData needs
    }

    public static function from(Model $model): static
    {
        $model->loadMissing(static::requiredRelations());  // Load if not already loaded

        return new self(
            id: $model->id,
            quantity: $model->quantity,
            set: SetResourceData::from($model->set),
        );
    }
}
```

**For nested relationships** (e.g., SetParts with Part and Color):
```php
protected static function requiredRelations(): array
{
    return ['setParts.part', 'setParts.color'];
}
```

**Benefits**:
- Self-documenting: ResourceData declares its own dependencies
- No N+1 queries: `collection()` method bulk-loads via `loadMissing()`
- Actions stay simple: No need to know what the response format requires

## When Generating ResourceData

1. First, read the model to understand its properties and relations
2. Identify which properties should be exposed in the API
3. Identify relations that need nested ResourceData classes
4. Check if nested ResourceData classes exist; if not, create them first
5. Use snake_case for all property names
6. If accessing relationships, override `requiredRelations()` to declare them
7. Add PHPStan ignore comments only when necessary (e.g., nullable relations)

---
name: resource-data
description: Create a ResourceData class for API responses based on Eloquent models
argument-hint: [ModelName]
allowed-tools: Read, Grep, Glob, Write, Edit
---

# ResourceData Skill

Create ResourceData DTO classes for API responses. Parse `$ARGUMENTS` for the model name.

## Workflow

1. Read the model for properties and relations
2. Decide which properties to expose
3. Check if nested ResourceData classes exist (create first if not)
4. Generate at `app/Http/Resources/{ModelName}ResourceData.php`

Read existing ResourceData classes for the template pattern.

## Key Rules

- `final readonly class` with `@extends ResourceData<ModelName>`
- **snake_case** properties (matching JSON output)
- `from(Model $model): static` factory + `collection()` for lists
- Base class handles: ISO8601 date formatting, enum-to-value conversion

## Relationship Loading (requiredRelations)

If accessing relations, override `requiredRelations()` to prevent N+1:

```php
protected static function requiredRelations(): array
{
    return ['set', 'setParts.color'];
}

public static function from(Model $model): static
{
    $model->loadMissing(static::requiredRelations());
    return new self(...);
}
```

## Nested Resources

| Type | Property | In from() |
|------|----------|-----------|
| Required relation | `SetResourceData $set` | `SetResourceData::from($model->set)` |
| Nullable relation | `?ColorResourceData $color` | `$model->color !== null ? ColorResourceData::from($model->color) : null` |
| Collection | `array $children` (with `@param` docblock) | `array_map(self::from(...), $model->children->all())` |

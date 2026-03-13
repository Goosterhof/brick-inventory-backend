---
name: form-request
description: Create Form Requests using the DTOFormRequest pattern with interface contracts
argument-hint: <Name> [--fields="field1:type,field2:type"]
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(composer lint)
---

# Form Request Skill

Create Form Requests with DTOFormRequest pattern. Parse `$ARGUMENTS` for name and optional fields.

## What Gets Created

1. **Interface**: `app/Contracts/{Domain}/{Name}Interface.php`
2. **Request**: `app/Http/Requests/{Domain}/{Name}Request.php`

### Name Mapping
- `Store{Subject}Request` → `Create{Subject}Interface` (Store maps to Create)
- `Update{Subject}Request` → `Update{Subject}Interface`

## Pattern (Non-Obvious Parts)

Read existing form requests for the full template. Key differences from standard Laravel:

- Extends `DTOFormRequest` (NOT `FormRequest`)
- `final readonly` class with typed constructor properties
- Implements the interface with PHP 8.4 property hooks
- Field names as `string` constants (used in `rules()` and `toDTO()`)
- `toDTO()` maps request data to constructor properties

## Interface Inheritance

### Pattern 1: Create Extends Update
When Create has **extra fields** beyond Update (e.g., `setNum` only needed on create):
```php
interface UpdateFamilySetInterface { public int $quantity { get; } }
interface CreateFamilySetInterface extends UpdateFamilySetInterface { public string $setNum { get; } }
```

### Pattern 2: Shared Base
When Create and Update have **identical fields**:
```php
interface StorageOptionDataInterface { public string $name { get; } }
interface CreateStorageOptionInterface extends StorageOptionDataInterface {}
interface UpdateStorageOptionInterface extends StorageOptionDataInterface {}
```

## Type Mapping in toDTO()

| PHP Type | Request Method | Nullable |
|----------|----------------|----------|
| `string` | `$request->string('f')->toString()` | `isNotFilled('f') ? null : ...` |
| `int` | `$request->integer('f')` | `isNotFilled('f') ? null : ...` |
| `bool` | `$request->boolean('f')` | Direct use |
| `Enum` (string) | `Enum::from($request->string('f')->toString())` | `isNotFilled` guard |
| `DateTimeInterface` | `CarbonImmutable::parse(...)` | `isNotFilled` guard |

## After Creation

1. Run `composer lint`
2. Tell user to update related Actions to accept the interface

---
name: form-request
description: Create Form Requests using the DTOFormRequest pattern with interface contracts
argument-hint: <Name> [--fields="field1:type,field2:type"]
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(composer lint)
---

# Form Request Skill

Create Form Requests that act as DTOs using the DTOFormRequest pattern with interface contracts.

## Usage

```
/form-request <Name> [--fields="field1:type,field2:type"]
```

Example:
```
/form-request CreateProduct --fields="name:string,price:int,description:?string"
```

## Pattern Overview

Form Requests in this project:
1. Extend `DTOFormRequest` (not Laravel's `FormRequest`)
2. Implement an interface with PHP 8.4 property hooks
3. Are `final readonly` classes with typed constructor properties
4. Use string constants for field names (DRY)
5. Have a `toDTO()` method that maps request data to properties

## Domain Convention

In this codebase, `{Domain}` refers to the subdirectory used to organize related Actions, Contracts, and Requests. The domain typically matches the primary model name (e.g., `StorageOption` model → `StorageOption` domain).

Directory structure:
- `app/Actions/{Domain}/` - Action classes
- `app/Contracts/{Domain}/` - Interface contracts
- `app/Http/Requests/{Domain}/` - Form Requests

## File Structure

When creating a new Form Request, create these files:

1. **Interface**: `app/Contracts/{Domain}/{Name}Interface.php`
2. **Request**: `app/Http/Requests/{Domain}/{Name}Request.php`

### Interface Naming Convention
The interface name should match the action verb:
- `Store{Subject}Request` → `Create{Subject}Interface` (maps Store to Create)
- `Update{Subject}Request` → `Update{Subject}Interface`

This ensures compatibility with Action classes which use `Create`, `Update`, `Delete`, `Get` verbs.

## Templates

### Interface Template

```php
<?php

declare(strict_types=1);

namespace App\Contracts\{Domain};

interface {Name}Interface
{
    public {type} ${propertyName} { get; }
    // Add more properties...
}
```

### Form Request Template

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\{Domain};

use App\Contracts\{Domain}\{Name}Interface;
use App\Http\Requests\DTOFormRequest;
use Illuminate\Http\Request;

final readonly class {Name}Request extends DTOFormRequest implements {Name}Interface
{
    public const string FIELD_NAME = 'field_name';
    // Add more constants...

    public function __construct(
        public string $fieldName,
        public ?string $optionalField = null,
        // Add more properties...
    ) {}

    public static function rules(Request $request): array
    {
        return [
            self::FIELD_NAME => ['required', 'string', 'max:255'],
            // Add more rules...
        ];
    }

    protected static function toDTO(Request $request): static
    {
        return new self(
            fieldName: $request->string(self::FIELD_NAME)->toString(),
            optionalField: $request->isNotFilled(self::OPTIONAL_FIELD)
                ? null
                : $request->string(self::OPTIONAL_FIELD)->toString(),
            // Map more fields...
        );
    }
}
```

## Interface Inheritance Pattern

When `Create` and `Update` interfaces share common fields, use interface inheritance to avoid duplication:

```php
// Base interface with shared fields
interface UpdateProductInterface
{
    public string $name { get; }
    public ?string $description { get; }
    public int $price { get; }
}

// Create interface extends Update and adds create-specific fields
interface CreateProductInterface extends UpdateProductInterface
{
    public string $sku { get; }  // Only needed on create
}
```

This pattern is useful when:
- Update operations use a subset of Create fields
- You want to ensure consistency between Create and Update
- You want to avoid duplicating property definitions

The Request classes then implement the appropriate interface:
- `StoreProductRequest implements CreateProductInterface`
- `UpdateProductRequest implements UpdateProductInterface`

## Type Mapping in toDTO()

| PHP Type | Request Method | Nullable Pattern |
|----------|----------------|------------------|
| `string` | `$request->string('field')->toString()` | `$request->isNotFilled('field') ? null : $request->string('field')->toString()` |
| `int` | `$request->integer('field')` | `$request->isNotFilled('field') ? null : $request->integer('field')` |
| `bool` | `$request->boolean('field')` | Direct use (defaults to false) |
| `array` | `$request->input('field', [])` | `$request->isNotFilled('field') ? [] : $request->input('field', [])` |
| `Enum` | `EnumClass::from($request->string('field')->toString())` | `$request->isNotFilled('field') ? null : EnumClass::from(...)` |
| `DateTimeInterface` | `CarbonImmutable::parse($request->string('field')->toString())` | `$request->isNotFilled('field') ? null : CarbonImmutable::parse($request->string('field')->toString())` |

**Note:** For `DateTimeInterface`, import `Carbon\CarbonImmutable` and use the interface type for flexibility.

## Updating Actions

When using the new Form Request, update the corresponding Action to:

1. Accept the interface instead of a concrete DTO
2. Use `#[CurrentUser]` attribute if authenticated user data is needed

```php
use App\Contracts\{Domain}\{Name}Interface;
use Illuminate\Container\Attributes\CurrentUser;

class {Name}Action
{
    public function __construct(
        #[CurrentUser] private readonly User $user,
        // Other dependencies...
    ) {}

    public function execute({Name}Interface $data): Model
    {
        // Use $data->propertyName directly
        // Use $this->user->family_id for tenant isolation
    }
}
```

## Updating Controllers

Controllers become simpler - just pass the request directly:

```php
public function store({Name}Request $request): JsonResponse
{
    $result = $this->action->execute($request);
    return new JsonResponse($result, 201);
}
```

## Unit Testing

In unit tests, use anonymous classes implementing the interface:

```php
$data = new class implements {Name}Interface {
    public string $fieldName = 'test value';
    public ?string $optionalField = null;
};

$action->execute($data);
```

## Checklist

When creating a new Form Request:

- [ ] Create interface in `app/Contracts/{Domain}/`
- [ ] Create request in `app/Http/Requests/{Domain}/`
- [ ] Request extends `DTOFormRequest`
- [ ] Request implements the interface
- [ ] Request is `final readonly`
- [ ] All field names have string constants
- [ ] `rules()` method uses constants for field names
- [ ] `toDTO()` properly handles nullable fields
- [ ] Update Action to accept interface
- [ ] Update Controller to pass request directly
- [ ] Update unit tests to use anonymous classes
- [ ] Run `composer lint && composer phpstan && composer test`

## Workflow

1. Parse the name from `$ARGUMENTS` (e.g., `StoreProduct`, `UpdateProduct`)
2. Determine the domain from the subject (e.g., `Product` → check existing domains or create new)
3. Map the request verb to interface verb:
   - `Store{Subject}` → interface is `Create{Subject}Interface`
   - `Update{Subject}` → interface is `Update{Subject}Interface`
4. Check if the interface already exists in `app/Contracts/{Domain}/`
5. If interface doesn't exist, create it
6. Create the Form Request that implements the interface
7. Run `composer lint` to format the code
8. Inform the user about updating related Actions to accept the interface

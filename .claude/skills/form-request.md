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

## File Structure

When creating a new Form Request, create these files:

1. **Interface**: `app/Contracts/{Domain}/{Name}Interface.php`
2. **Request**: `app/Http/Requests/{Domain}/{Name}Request.php`

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

## Type Mapping in toDTO()

| PHP Type | Request Method | Nullable Pattern |
|----------|----------------|------------------|
| `string` | `$request->string('field')->toString()` | `$request->isNotFilled('field') ? null : $request->string('field')->toString()` |
| `int` | `$request->integer('field')` | `$request->isNotFilled('field') ? null : $request->integer('field')` |
| `bool` | `$request->boolean('field')` | Direct use (defaults to false) |
| `array` | `$request->array('field')` | Direct use or check `isNotFilled()` |
| `Enum` | `EnumClass::from($request->integer('field'))` | `$request->isNotFilled('field') ? null : EnumClass::from(...)` |
| `Carbon` | `$request->date('field')` | `$request->isNotFilled('field') ? null : $request->date('field')` |

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

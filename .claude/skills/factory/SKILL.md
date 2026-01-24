---
name: factory
description: Generate a factory from an existing model
argument-hint: ModelName
allowed-tools: Read, Grep, Glob, Write, Bash(composer lint, composer phpstan, composer test:arch)
---

# Factory Skill

Generate a Laravel factory based on an existing Eloquent model.

## Arguments

Parse `$ARGUMENTS` to get the model name (e.g., `User`, `StorageOption`).

## Workflow

### Step 1: Find the Model

Search for the model file:

1. Look in `app/Models/` for `{ModelName}.php`
2. If not found, search subdirectories: `app/Models/**/{ModelName}.php`
3. If no model found, stop and tell the user:
   > "No model found for `{ModelName}`. Please create one first using the `/model` skill."

Record the model's namespace path (e.g., `App\Models\Inventory\Part`).

### Step 2: Parse the Model

Extract from the model's `@property` PHPDoc annotations:

- **Property names**: The column names
- **Property types**: PHP types including nullability
- **Enum types**: Properties with enum class types

Extract relationships by looking for methods returning:
- `BelongsTo` - indicates a `*_id` foreign key
- Self-referential relationships (e.g., `parent_id` pointing to same model)

### Step 3: Find the Migration

Search for the corresponding migration to extract constraints:

1. Convert model name to table name (e.g., `StorageOption` → `storage_options`)
2. Look in `database/migrations/` for `Schema::create('{table_name}'`
3. Extract:
   - `unique()` constraints
   - `nullable()` columns
   - Default values

### Step 4: Determine Factory Path

Mirror the model's namespace structure:

| Model Location | Factory Location |
|----------------|------------------|
| `App\Models\User` | `database/factories/UserFactory.php` |
| `App\Models\Inventory\Part` | `database/factories/Inventory/PartFactory.php` |

Create subdirectories if needed.

### Step 5: Map Properties to Faker Methods

Use these mappings based on column type and name:

**By column name patterns:**

| Pattern | Faker Method |
|---------|--------------|
| `*_id` (external, unique) | `fake()->unique()->randomNumber(4)` |
| `email` | `fake()->unique()->safeEmail()` |
| `name` (person) | `fake()->name()` |
| `name` (thing) | `fake()->words(2, true)` |
| `password` | `Hash::make('password')` (with static caching) |
| `*_url` | `fake()->url()` |
| `*_date` | `fake()->date()` |

**By type:**

| Type | Faker Method |
|------|--------------|
| `string` | `fake()->word()` |
| `string` (nullable) | `fake()->optional()->word()` |
| `text` | `fake()->sentence()` |
| `text` (nullable) | `fake()->optional()->sentence()` |
| `int` | `fake()->randomNumber()` |
| `int` (with range context) | `fake()->numberBetween(1, 10)` |
| `bool` | `fake()->boolean()` |
| `date` | `fake()->date()` |
| `datetime` / `Carbon` | `fake()->dateTime()` |
| `array` / `json` | `[]` |
| Enum class | `fake()->randomElement(EnumClass::cases())` |

**For foreign keys:**

| Constraint | Faker Method |
|------------|--------------|
| Required `*_id` | `RelatedModel::factory()` |
| Nullable `*_id` | `null` |

### Step 6: Generate State Methods

Create state methods for:

**1. Nullable relationships:**

```php
public function withParent(ParentModel $parent): static
{
    return $this->state(fn (array $attributes): array => [
        'parent_id' => $parent->id,
    ]);
}
```

**2. Self-referential relationships (e.g., `parent_id` pointing to same model):**

```php
public function withParent(ModelName $parent): static
{
    return $this->state(fn (array $attributes): array => [
        // Include family_id if the model is tenant-scoped
        'family_id' => $parent->family_id,
        'parent_id' => $parent->id,
    ]);
}
```

**3. All relationships get a state method** for flexibility in tests:

```php
public function forFamily(Family $family): static
{
    return $this->state(fn (array $attributes): array => [
        'family_id' => $family->id,
    ]);
}

public function forUser(User $user): static
{
    return $this->state(fn (array $attributes): array => [
        'user_id' => $user->id,
    ]);
}
```

**Naming conventions:**
- Nullable/optional: `with{Relationship}` (e.g., `withParent`)
- Required: `for{Relationship}` (e.g., `forFamily`, `forUser`)

### Step 7: Generate the Factory

Create the file with this structure:

```php
<?php

declare(strict_types=1);

namespace Database\Factories\{SubNamespace};

use App\Models\{SubNamespace}\{ModelName};
// ... other imports (related models, enums)
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<{ModelName}>
 */
class {ModelName}Factory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Properties mapped to faker methods
        ];
    }

    // State methods for relationships
}
```

**For models with password (like User):**

```php
protected static ?string $password;

public function definition(): array
{
    return [
        // ...
        'password' => static::$password ??= Hash::make('password'),
    ];
}
```

### Step 8: Overwrite Handling

If the factory file already exists, overwrite it silently without asking.

### Step 9: Validation

After generating the factory:

1. Run `composer lint` to fix code style
2. Run `composer phpstan` to check for type errors
3. Run `composer test:arch` to verify architecture rules

All must pass. If any fail, fix the issues before reporting completion.

## Code Conventions

### Strict Types

Always start with:

```php
<?php

declare(strict_types=1);
```

### PHPDoc

Include the generic type annotation:

```php
/**
 * @extends Factory<ModelName>
 */
```

### Definition Method

Always include the PHPDoc block:

```php
/**
 * Define the model's default state.
 *
 * @return array<string, mixed>
 */
public function definition(): array
```

### State Method Signature

Use this exact signature pattern:

```php
public function methodName(Type $param): static
{
    return $this->state(fn (array $attributes): array => [
        'column' => $value,
    ]);
}
```

### Imports

Only import what's needed:

- Related model classes for factory calls and state methods
- Enum classes if used
- `Illuminate\Support\Facades\Hash` if password field exists

## Example Output

For a model like:

```php
/**
 * @property positive-int $id
 * @property int $family_id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $description
 * @property int|null $row
 * @property int|null $column
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StorageOption extends Model
{
    public function family(): BelongsTo { ... }
    public function parent(): BelongsTo { ... }
}
```

Generate:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Family;
use App\Models\StorageOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StorageOption>
 */
class StorageOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'parent_id' => null,
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'row' => null,
            'column' => null,
        ];
    }

    public function forFamily(Family $family): static
    {
        return $this->state(fn (array $attributes): array => [
            'family_id' => $family->id,
        ]);
    }

    public function withParent(StorageOption $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'family_id' => $parent->family_id,
            'parent_id' => $parent->id,
        ]);
    }
}
```

## Example with Enums

For a model with enum properties:

```php
/**
 * @property FamilySetStatus $status
 */
class FamilySet extends Model
```

Generate:

```php
use App\Enums\FamilySetStatus;

public function definition(): array
{
    return [
        'status' => fake()->randomElement(FamilySetStatus::cases()),
    ];
}
```

## After Generation

Report to the user:
1. The created factory file path
2. Any state methods that were generated
3. Confirmation that all validation passed

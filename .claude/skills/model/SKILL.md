---
name: model
description: Generate an Eloquent model from an existing migration
argument-hint: ModelName
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(php artisan)
---

# Model Skill

Generate an Eloquent model based on an existing database migration.

## Arguments

Parse `$ARGUMENTS` to get the model name (e.g., `Drawer`, `StorageOption`).

## Workflow

### Step 1: Find the Migration

Search for a migration that creates the table for this model:

1. Convert model name to table name (e.g., `StorageOption` → `storage_options`)
2. Look in `database/migrations/` for a file containing `create_{table_name}_table` or `Schema::create('{table_name}'`
3. If no migration found, stop and tell the user:
   > "No migration found for `{table_name}`. Please create one first using the `/migration` skill."

### Step 2: Parse the Migration

Extract from the migration:

- **Columns**: name, type, nullable, default values
- **Foreign keys**: `foreignId()` or `->constrained()` calls indicate relationships
- **Special columns**: `family_id` indicates tenant-scoped model

Map migration types to PHP/PHPDoc types:

| Migration Type | PHP Type | PHPDoc Type |
|----------------|----------|-------------|
| `id()` | int | `positive-int` |
| `string()`, `text()` | string | `string` |
| `integer()`, `unsignedInteger()`, `tinyInteger()` | int | `int` |
| `boolean()` | bool | `bool` |
| `decimal()`, `float()`, `double()` | float | `float` |
| `date()`, `dateTime()`, `timestamp()` | Carbon | `Carbon` |
| `json()`, `jsonb()` | array | `array` |
| `foreignId()` | int | `int` |
| `timestamps()` | - | `Carbon\|null $created_at`, `Carbon\|null $updated_at` |
| `softDeletes()` | - | `Carbon\|null $deleted_at` |

For nullable columns, append `|null` to the type.

### Step 3: Identify Relationships

**Automatic relationships:**

1. **`family_id`** → Always add `family(): BelongsTo<Family, $this>`
2. **Other `*_id` foreign keys** → Add `BelongsTo` relationship (e.g., `user_id` → `user(): BelongsTo<User, $this>`)

**Check other models for inverse relationships:**

- Search other models for `hasMany`, `hasOne`, `belongsToMany` pointing to this model
- If found, add the inverse relationship

**If relationships are unclear:**

Ask the user:
> "I found `{column}_id`. Should this model have a `belongsTo` relationship with `{Model}`? (y/n)"
> "Should this model have any `hasMany` or `hasOne` relationships? If so, which models?"

### Step 4: Generate the Model

Create the file at `app/Models/{ModelName}.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\{ModelName}Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// ... other imports based on relationships

/**
 * @property positive-int $id
 * @property string $name
 * ... all properties from migration
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class {ModelName} extends Model
{
    /** @use HasFactory<{ModelName}Factory> */
    use HasFactory;

    // Relationships go here with PHPDoc return types
}
```

## Code Conventions

### No Mass Assignment Properties

Do NOT add `$fillable` or `$guarded` unless the model has secure properties like `password`. In that case:

```php
/** @var list<string> */
protected $guarded = ['password'];
```

### Property Annotations

Always include `@property` annotations for ALL columns:

```php
/**
 * @property positive-int $id
 * @property int $family_id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
```

### Relationship PHPDoc

All relationships must have return type annotations:

```php
/**
 * @return BelongsTo<Family, $this>
 */
public function family(): BelongsTo
{
    return $this->belongsTo(Family::class);
}

/**
 * @return HasMany<Part, $this>
 */
public function parts(): HasMany
{
    return $this->hasMany(Part::class);
}
```

### Casts Method

If the model has columns that need casting (enums, dates other than timestamps, booleans, arrays), add the `casts()` method:

```php
/**
 * @return array<string, string>
 */
protected function casts(): array
{
    return [
        'status' => SomeEnum::class,
        'is_active' => 'boolean',
        'metadata' => 'array',
        'published_at' => 'date',
    ];
}
```

### Imports

Only import what's needed:

- `Carbon\Carbon` if date properties exist
- Relationship classes from `Illuminate\Database\Eloquent\Relations\*`
- Related model classes
- Enum classes if used in casts

## Example Output

For a migration like:

```php
Schema::create('drawers', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('family_id')->constrained()->onDelete('cascade');
    $table->foreignId('cabinet_id')->constrained()->onDelete('cascade');
    $table->string('label');
    $table->text('description')->nullable();
    $table->unsignedInteger('position')->default(1);
    $table->timestamps();
});
```

Generate:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\DrawerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property positive-int $id
 * @property int $family_id
 * @property int $cabinet_id
 * @property string $label
 * @property string|null $description
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Drawer extends Model
{
    /** @use HasFactory<DrawerFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return BelongsTo<Cabinet, $this>
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }
}
```

## After Generation

1. Run `composer lint` to ensure code style compliance
2. Run `composer phpstan` to check for type errors
3. Report the created file path to the user

---
name: migration
description: Generate an Eloquent migration from a model name
argument-hint: <ModelName>
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(composer lint), Skill
---

# Migration Skill

You are helping create Laravel migrations following strict project conventions.

## Arguments

Parse `$ARGUMENTS` to get the model name (e.g., `Drawer`, `Cabinet`, `ColorPart`).

The name should be in PascalCase. The skill will convert it to a snake_case table name.

## Migration Type Detection

The skill auto-detects the migration type:

### 1. Pivot Table Detection

Check if the model name is a combination of two existing models:

```bash
# List existing models
ls app/Models/
```

If the name can be split into two existing model names (e.g., `ColorPart` → `Color` + `Part`):
- Create a pivot table migration
- Table name follows Laravel convention: alphabetical order, snake_case (e.g., `color_part`)
- Include both foreign keys

### 2. Create vs Modify Detection

Search for existing migrations for this table:

```bash
# Check for existing migration (convert ModelName to snake_case plural)
ls database/migrations/*_create_{table_name}_table.php 2>/dev/null
```

- If **no migration exists** → Create new table migration
- If **migration exists** → Create modify migration to add columns

## Workflow

### Step 1: Determine Migration Type

1. Parse model name from `$ARGUMENTS`
2. Convert to snake_case plural for table name (e.g., `Drawer` → `drawers`, `StorageOption` → `storage_options`)
3. Check for pivot table (combined model names)
4. Check for existing migration

### Step 2: Gather Column Information

Ask the user:

> "What columns do you need for the `{table_name}` table?"

Wait for plain English description (e.g., "A label string, position integer defaulting to 1, and belongs to Cabinet").

### Step 3: Parse Column Description

Convert plain English to column definitions:

| User Says | Column Type |
|-----------|-------------|
| "string", "name", "label", "title" | `$table->string('column')` |
| "text", "description", "content" | `$table->text('column')` |
| "integer", "number", "count", "position" | `$table->integer('column')` or `$table->unsignedInteger('column')` |
| "boolean", "flag", "is_*", "has_*" | `$table->boolean('column')` |
| "decimal", "price", "amount" | `$table->decimal('column', 10, 2)` |
| "date" | `$table->date('column')` |
| "datetime", "timestamp" | `$table->timestamp('column')` |
| "belongs to X", "reference to X", "X_id" | `$table->foreignId('x_id')->constrained()` |
| "nullable" | Add `->nullable()` modifier |
| "default X" | Add `->default(X)` modifier |
| "unique" | Add `->unique()` modifier |

### Step 4: Validate Foreign Keys

For each foreign key reference, verify the related table's migration exists:

```bash
# Example: checking for cabinets table
ls database/migrations/*_create_cabinets_table.php 2>/dev/null
```

If the migration does NOT exist:
> "Error: Cannot create foreign key to `cabinets` table - no migration found. Create the `Cabinet` migration first."

Stop and do not proceed.

### Step 5: Determine Tenant Scoping

Apply smart inference for `family_id`:

**Include `family_id`** (user-owned/tenant-scoped data):
- Storage-related: drawers, cabinets, shelves, containers, storage options
- User collections: inventories, wishlists, builds, projects
- User preferences: settings, configurations
- Anything that "belongs to" a family/user

**Exclude `family_id`** (shared/reference data):
- LEGO reference data: colors, parts, sets, themes, categories
- System data: jobs, cache, tokens
- Global lookup tables

If unclear, ask:
> "Should `{table_name}` be tenant-scoped (owned by a family) or shared reference data?"

### Step 6: Generate Migration File

#### File Naming

**Create migration:**
```
{date}_create_{table_name}_table.php
```

**Modify migration:**
```
{date}_add_{column_names}_to_{table_name}_table.php
```

Use `_and_` to join multiple column names (e.g., `add_label_and_position_to_drawers_table`).

Generate the date using:
```bash
date +%Y_%m_%d_%H%M%S
```

#### Template: Create Table (Tenant-Scoped)

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{table_name}', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id')->constrained();
            // Business columns here
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{table_name}');
    }
};
```

#### Template: Create Table (Shared/Reference)

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{table_name}', function (Blueprint $table): void {
            $table->id();
            // Business columns here
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{table_name}');
    }
};
```

#### Template: Pivot Table

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{table_name}', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('{model1_id}')->constrained();
            $table->foreignId('{model2_id}')->constrained();
            // Additional pivot columns here (if any)
            $table->timestamps();

            $table->unique(['{model1_id}', '{model2_id}']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{table_name}');
    }
};
```

#### Template: Modify Table (Add Columns)

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('{table_name}', function (Blueprint $table): void {
            // New columns here
        });
    }

    public function down(): void
    {
        Schema::table('{table_name}', function (Blueprint $table): void {
            // Drop columns in reverse order
            // Use $table->dropConstrainedForeignId('x_id') for foreign keys
            // Use $table->dropColumn('column') for regular columns
        });
    }
};
```

## Code Conventions

**CRITICAL - No Cascade Deletes:**
- NEVER use `->onDelete('cascade')` or `->cascadeOnDelete()`
- Deletion cascading must be handled in Action classes (business logic)
- Only use `->constrained()` for foreign keys

**Other conventions:**
- Always start with `declare(strict_types=1);`
- Use anonymous class syntax: `return new class extends Migration`
- Add `void` return type to `up()`, `down()`, and Schema callbacks
- Foreign keys: `$table->foreignId('x_id')->constrained()`
- Nullable foreign keys: `$table->foreignId('x_id')->nullable()->constrained()`

## Post-Generation Steps

### Step 7: Run Linter

```bash
composer lint
```

### Step 8: Auto-Chain to Model Skill

After migration is created, automatically invoke the model skill:

1. Check if model already exists:
```bash
ls app/Models/{ModelName}.php 2>/dev/null
```

2. If model does NOT exist:
   - Invoke `/model {ModelName}` skill automatically

3. If model DOES exist:
   - Ask: "Model `{ModelName}` already exists. Should I update it with the new columns from this migration?"
   - If yes, invoke `/model {ModelName}` to regenerate/update the model

## Example Workflows

### Example 1: New Tenant-Scoped Table

User runs: `/migration Drawer`

1. Check pivot: `Drawer` is not two models → not a pivot
2. Check existing: No `create_drawers_table` migration → create new
3. Ask: "What columns do you need for the `drawers` table?"
4. User: "A label string, position integer defaulting to 0, and belongs to Cabinet"
5. Validate: Check `cabinets` migration exists ✓
6. Infer scope: Drawer is storage-related → tenant-scoped (include `family_id`)
7. Generate `{date}_create_drawers_table.php`:
   ```php
   $table->id();
   $table->foreignId('family_id')->constrained();
   $table->foreignId('cabinet_id')->constrained();
   $table->string('label');
   $table->integer('position')->default(0);
   $table->timestamps();
   ```
8. Run `composer lint`
9. Auto-invoke `/model Drawer`

### Example 2: Pivot Table

User runs: `/migration ColorPart`

1. Check pivot: `Color` model exists, `Part` model exists → pivot table!
2. Table name: `color_part` (alphabetical)
3. Ask: "Any additional columns for the `color_part` pivot table besides the foreign keys?"
4. User: "Just a quantity integer"
5. Generate `{date}_create_color_part_table.php`:
   ```php
   $table->id();
   $table->foreignId('color_id')->constrained();
   $table->foreignId('part_id')->constrained();
   $table->unsignedInteger('quantity')->default(0);
   $table->timestamps();

   $table->unique(['color_id', 'part_id']);
   ```
6. Run `composer lint`
7. Auto-invoke `/model ColorPart`

### Example 3: Modify Existing Table

User runs: `/migration User`

1. Check pivot: Not a pivot
2. Check existing: `create_users_table` migration exists → modify migration
3. Ask: "What columns do you want to add to the `users` table?"
4. User: "A nullable phone string and a boolean is_admin defaulting to false"
5. Generate `{date}_add_phone_and_is_admin_to_users_table.php`:
   ```php
   // up()
   $table->string('phone')->nullable();
   $table->boolean('is_admin')->default(false);

   // down()
   $table->dropColumn('is_admin');
   $table->dropColumn('phone');
   ```
6. Run `composer lint`
7. Model exists → ask to update → invoke `/model User`

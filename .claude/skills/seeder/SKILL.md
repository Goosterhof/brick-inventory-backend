---
name: seeder
description: Create database seeders for models
argument-hint: <ModelName>
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(composer lint)
---

# Seeder Skill

You are helping create database seeders for a Laravel project following strict conventions.

## Arguments

Parse `$ARGUMENTS` to get the model name (e.g., `Color`, `StorageOption`, `Part`).

The name should NOT include the `Seeder` suffix - it will be added automatically.

## Workflow

### Step 1: Validate the Model Exists

Check if the model exists:

```bash
ls app/Models/{ModelName}.php 2>/dev/null
```

If the model does NOT exist:
> "Error: Model `{ModelName}` not found at `app/Models/{ModelName}.php`. Please create the model first using the `/model` skill."

Stop and do not proceed.

### Step 2: Determine Tenant Scoping

Read the model file to check if it has a `family_id` property:

```bash
grep -l "family_id" app/Models/{ModelName}.php
```

- If `family_id` exists → This is a **tenant-scoped** seeder (requires `Family` parameter)
- If no `family_id` → This is a **reference data** seeder (no family required)

### Step 3: Ask for Data Source

Ask the user:

> "Should the `{ModelName}Seeder` use:
> 1. **Factory** - Generate fake data using `{ModelName}::factory()->count(50)->create()`
> 2. **Static data** - Hardcoded array of real data
>
> Which approach? (1 or 2)"

Wait for the user's response before proceeding.

### Step 4a: Factory-Based Seeder

If the user chose **Factory**:

#### Check Factory Exists

```bash
ls database/factories/{ModelName}Factory.php 2>/dev/null
```

If factory does NOT exist:
> "No factory found for `{ModelName}`. Would you like me to create one? (y/n)"

- If yes: Guide the user to create the factory or help create a basic one
- If no: Stop and ask user to create it first

#### Generate Factory-Based Seeder

**For reference data (no family_id):**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\{ModelName};
use Illuminate\Database\Seeder;

class {ModelName}Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        {ModelName}::factory()
            ->count(50)
            ->create();
    }
}
```

**For tenant-scoped data (has family_id):**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Family;
use App\Models\{ModelName};
use Illuminate\Database\Seeder;

class {ModelName}Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(Family $family): void
    {
        {ModelName}::factory()
            ->count(50)
            ->create(['family_id' => $family->id]);
    }
}
```

### Step 4b: Static Data Seeder

If the user chose **Static data**:

Ask the user:
> "Please provide the static data for `{ModelName}`. List the records you want to seed, or describe them and I'll help format them."

Wait for the user's response.

#### Generate Static Data Seeder

**For reference data (no family_id):**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\{ModelName};
use Illuminate\Database\Seeder;

class {ModelName}Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            // User-provided data here
            ['column1' => 'value1', 'column2' => 'value2'],
            ['column1' => 'value3', 'column2' => 'value4'],
        ];

        foreach ($records as $record) {
            ${modelName} = new {ModelName}();
            // Assign each property explicitly (no mass assignment)
            ${modelName}->column1 = $record['column1'];
            ${modelName}->column2 = $record['column2'];
            ${modelName}->save();
        }
    }
}
```

**For tenant-scoped data (has family_id):**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Family;
use App\Models\{ModelName};
use Illuminate\Database\Seeder;

class {ModelName}Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(Family $family): void
    {
        $records = [
            // User-provided data here
            ['column1' => 'value1', 'column2' => 'value2'],
            ['column1' => 'value3', 'column2' => 'value4'],
        ];

        foreach ($records as $record) {
            $modelName = new {ModelName}();
            $modelName->family_id = $family->id;
            // Assign each property explicitly (no mass assignment)
            $modelName->column1 = $record['column1'];
            $modelName->column2 = $record['column2'];
            $modelName->save();
        }
    }
}
```

## Important: No Mass Assignment

This project does NOT use mass assignment. When creating records from static data, always assign properties explicitly:

```php
// CORRECT - explicit assignment
$model = new Model();
$model->name = $data['name'];
$model->save();

// WRONG - mass assignment (do NOT use)
Model::create($data);
```

## Step 5: Register in DatabaseSeeder

After creating the seeder, update `database/seeders/DatabaseSeeder.php` to include it.

Read the current DatabaseSeeder:

```bash
cat database/seeders/DatabaseSeeder.php
```

**If `$this->call([...])` already exists:** Add the new seeder to the array.

**If no `$this->call()` exists:** Add it to the `run()` method.

**For reference data seeders:**

```php
$this->call([
    // ... existing seeders
    {ModelName}Seeder::class,
]);
```

**For tenant-scoped seeders:**

These require a `Family` instance, so they must be called differently:

```php
// Create or get a family first
$family = Family::factory()->create();

// Then call the seeder with the family
$this->callWith({ModelName}Seeder::class, ['family' => $family]);
```

Or document for the user that they need to call it with:
```php
(new {ModelName}Seeder())->run($family);
```

## Step 6: Run Linter

```bash
composer lint
```

## Step 7: Report to User

Summarize what was created:

> "Created `database/seeders/{ModelName}Seeder.php` and registered it in `DatabaseSeeder`.
>
> Run with: `php artisan db:seed --class={ModelName}Seeder`"

For tenant-scoped seeders, also mention:
> "Note: This seeder requires a `Family` instance. Pass it when calling: `(new {ModelName}Seeder())->run($family);`"

## File Location

All seeders go in: `database/seeders/{ModelName}Seeder.php`

## Code Conventions

- Always start with `declare(strict_types=1);`
- Use `void` return type on `run()` method
- No mass assignment - assign properties explicitly
- Import all used classes at the top
- Follow PSR-12 coding standards

## Example Workflows

### Example 1: Factory-Based Reference Data Seeder

User runs: `/seeder Color`

1. Check model: `app/Models/Color.php` exists ✓
2. Check tenant scope: No `family_id` → reference data
3. Ask: "Factory or static data?"
4. User: "Factory"
5. Check factory: `database/factories/ColorFactory.php` exists ✓
6. Generate `database/seeders/ColorSeeder.php`:
   ```php
   public function run(): void
   {
       Color::factory()
           ->count(50)
           ->create();
   }
   ```
7. Register in `DatabaseSeeder`
8. Run `composer lint`
9. Report success

### Example 2: Static Data Tenant-Scoped Seeder

User runs: `/seeder StorageOption`

1. Check model: `app/Models/StorageOption.php` exists ✓
2. Check tenant scope: Has `family_id` → tenant-scoped
3. Ask: "Factory or static data?"
4. User: "Static data"
5. Ask: "Please provide the static data..."
6. User provides data
7. Generate `database/seeders/StorageOptionSeeder.php` with `run(Family $family)`
8. Register in `DatabaseSeeder` with note about family parameter
9. Run `composer lint`
10. Report success with usage instructions

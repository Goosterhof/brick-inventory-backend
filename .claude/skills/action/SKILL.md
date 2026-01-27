---
name: action
description: Create Action classes following project conventions
argument-hint: <ActionName>
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(composer lint)
---

# Action Skill

You are helping create Action classes for a Laravel project following strict conventions.

## Arguments

Parse `$ARGUMENTS` to get the action name (e.g., `CreateStorageOption`, `UpdateFamilySet`, `GetUser`).

The name should NOT include the `Action` suffix - it will be added automatically.

## Domain Convention

In this codebase, `{Domain}` refers to the subdirectory used to organize related Actions, Contracts, and Requests. The domain typically matches the primary model name (e.g., `StorageOption` model → `StorageOption` domain).

Directory structure:
- `app/Actions/{Domain}/` - Action classes
- `app/Contracts/{Domain}/` - Interface contracts
- `app/Http/Requests/{Domain}/` - Form Requests

## Naming Conventions

### Standard Verbs
Actions typically use one of these four verbs: `Create`, `Update`, `Delete`, `Get`

If the user provides a different verb, suggest the appropriate standard verb:
- `Store`, `Add`, `Register` → suggest `Create`
- `Destroy`, `Remove` → suggest `Delete`
- `Show`, `Index`, `Fetch`, `List` → suggest `Get`

### Extended Verbs (when standard verbs don't fit)
Some operations need more specific verbs to accurately describe behavior:

| Verb | Use When | Example |
|------|----------|---------|
| `Upsert` | Create-or-update based on identifier | `UpsertSetAction` - creates if not exists, updates if exists |
| `Assign` | Linking/associating records with upsert semantics | `AssignPartToStorageAction` - assigns part to storage, updates if already assigned |
| `Store` | Bulk persistence of related data | `StoreSetPartsAction` - stores multiple parts for a set |

**Naming accuracy over consistency**: Choose a verb that accurately reflects the action's behavior. A misleading name (e.g., `Create*` for upsert logic) causes more confusion than using a non-standard verb.

Example response for non-standard verb:
> "The verb '{verb}' is not standard. Based on the action's purpose:
> - If it only creates new records → use `Create`
> - If it creates OR updates existing records → use `Upsert` or `Assign`
> Which behavior does this action need?"

### Action Name Format
- Class name: `{Verb}{Subject}Action` (e.g., `CreateStorageOptionAction`)
- File name: `{Verb}{Subject}Action.php`

## Directory Structure

### Infer Domain from Context
Determine the appropriate domain based on the primary model:

- If the action works with `FamilySet` → `app/Actions/FamilySet/`
- If the action works with `StorageOption` or `StorageOptionPart` → `app/Actions/StorageOption/`
- If the action works with `Auth` or `User` registration → `app/Actions/Auth/`
- If unclear or general purpose → `app/Actions/` (root)

Check existing subdirectories first:
```bash
ls app/Actions/
```

## Action Type Templates

Infer the action type from the verb in the name:

### Create Action (`Create*`)
- Constructor injects the model (for `newInstance()`) and optionally the current user via `#[CurrentUser]`
- Execute takes an interface, returns the created model
- Uses `$this->model->newInstance()` pattern

```php
<?php

declare(strict_types=1);

namespace App\Actions\{Domain};

use App\Contracts\{Domain}\Create{Subject}Interface;
use App\Models\{Subject};
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class Create{Subject}Action
{
    public function __construct(
        private readonly {Subject} ${subject},
        #[CurrentUser]
        private readonly User $user,
    ) {}

    public function execute(Create{Subject}Interface $data): {Subject}
    {
        ${subject} = $this->{subject}->newInstance();
        ${subject}->family_id = $this->user->family_id;
        // Set properties from $data
        ${subject}->save();

        return ${subject};
    }
}
```

### Update Action (`Update*`)
- NO constructor (model passed to execute)
- Execute takes the model instance + interface, returns the updated model

```php
<?php

declare(strict_types=1);

namespace App\Actions\{Domain};

use App\Contracts\{Domain}\Update{Subject}Interface;
use App\Models\{Subject};

class Update{Subject}Action
{
    public function execute({Subject} ${subject}, Update{Subject}Interface $data): {Subject}
    {
        // Update properties from $data
        ${subject}->save();

        return ${subject};
    }
}
```

### Delete Action (`Delete*`)
- NO constructor (model passed to execute)
- Execute takes the model instance, returns void

```php
<?php

declare(strict_types=1);

namespace App\Actions\{Domain};

use App\Models\{Subject};

class Delete{Subject}Action
{
    public function execute({Subject} ${subject}): void
    {
        ${subject}->delete();
    }
}
```

### Get Action (`Get*`)

Get actions have three variants depending on use case:

#### Variant 1: Query by Identifier
- Constructor injects the model (for `newQuery()`)
- Execute takes an identifier (string, int), returns the model

```php
<?php

declare(strict_types=1);

namespace App\Actions\{Domain};

use App\Models\{Subject};

class Get{Subject}Action
{
    public function __construct(
        private readonly {Subject} ${subject},
    ) {}

    public function execute(string $identifier): {Subject}
    {
        return $this->{subject}->newQuery()
            ->where('column', $identifier)
            ->firstOrFail();
    }
}
```

#### Variant 2: Load Relationships on Existing Model
When using route model binding, the model instance already exists. Use this variant to load relationships:
- NO constructor (model passed to execute)
- Execute takes the model instance, loads relationships, returns the model

```php
<?php

declare(strict_types=1);

namespace App\Actions\{Domain};

use App\Models\{Subject};

class Get{Subject}Action
{
    public function execute({Subject} ${subject}): {Subject}
    {
        ${subject}->load(['relationship1', 'relationship2']);

        return ${subject};
    }
}
```

#### Variant 3: Query Collection (Plural Subject)
For fetching multiple records, use a plural subject name (e.g., `GetFamilySetsAction`):
- Constructor injects the model (for `newQuery()`)
- Execute takes query parameters (e.g., User for tenant scoping), returns Collection

```php
<?php

declare(strict_types=1);

namespace App\Actions\{Domain};

use App\Models\{Subject};
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class Get{Subjects}Action
{
    public function __construct(
        private readonly {Subject} ${subject},
    ) {}

    /**
     * @return Collection<int, {Subject}>
     */
    public function execute(User $user): Collection
    {
        return $this->{subject}->newQuery()
            ->where('family_id', $user->family_id)
            ->with(['relationship'])
            ->get();
    }
}
```

## Interface Handling

### For Create/Update Actions
1. First, check if a fitting interface already exists in `app/Contracts/{Domain}/`:
   - `Create{Subject}Interface` for Create actions
   - `Update{Subject}Interface` for Update actions

2. List existing interfaces:
```bash
ls app/Contracts/
```

3. If a fitting interface exists, use it.

4. If NO fitting interface exists, inform the user:
> "No existing interface found for this action. Please use the `/form-request` skill to create `{Store|Update}{Subject}Request` first (which creates the interface), then run this skill again."

Do NOT create interfaces inline - delegate to the `/form-request` skill which creates both the interface and the form request.

## Model Injection Rules

Apply these rules based on how the model is used:

| Usage | Pattern | Reason |
|-------|---------|--------|
| Query building (`newQuery()`, `newInstance()`) | Constructor injection | Easier to mock in tests |
| Modifying specific instance | Execute parameter | Cleaner API, instance already exists |

## Avoiding Action Overlap

Before creating a new action, check for existing actions that might already handle the same logic:

```bash
ls app/Actions/
ls app/Actions/{Domain}/
```

### Delegation Pattern
When an action needs logic that another action provides, **delegate** rather than duplicate:

```php
// Good - GetSetAction delegates to UpsertSetAction
class GetSetAction
{
    public function __construct(
        private readonly LegoDataServiceInterface $legoDataService,
        private readonly UpsertSetAction $upsertSetAction,  // Delegate
        private readonly Set $set,
    ) {}

    public function execute(string $setNum): Set
    {
        $set = $this->set->newQuery()->where('set_num', $setNum)->first();

        if ($set instanceof Set) {
            return $set;
        }

        // Delegate creation to UpsertSetAction instead of duplicating logic
        $legoSetData = $this->legoDataService->fetchSet($setNum);
        return $this->upsertSetAction->execute($legoSetData);
    }
}
```

### Common Overlap Patterns to Avoid
- `Get*Action` and `Upsert*Action` both creating records → `Get*` should delegate to `Upsert*`
- `Create*Action` with update logic → rename to `Upsert*` or `Assign*`
- Multiple actions calling the same service method with persistence → create orchestration action

## Code Quality Requirements

All actions MUST:
1. Start with `declare(strict_types=1);`
2. Use `readonly` for constructor properties
3. Have proper type hints for all parameters and return types
4. Follow PSR-12 coding standards

## Workflow

1. Parse the action name from `$ARGUMENTS`
2. Validate/suggest verb if non-standard
3. Determine the subject (model) from the name
4. Infer subdirectory from context
5. Determine action type from verb
6. Check for existing interface (Create/Update only)
7. If interface missing, stop and ask user to create it with `/form-request` skill
8. Generate the action file
9. Run `composer lint` to format the code
10. Invoke `/unit-test {ActionName}Action` to generate tests

## Example Workflow

User runs: `/action CreateDrawer`

1. Verb: `Create` (standard, no prompt needed)
2. Subject: `Drawer`
3. Subdirectory: Check if Drawer relates to StorageOption → likely `app/Actions/StorageOption/`
4. Type: Create action template
5. Check Interface: Look for `CreateDrawerInterface` in `app/Contracts/Drawer/` or `app/Contracts/StorageOption/`
6. If missing: "No interface found. Please run `/form-request StoreDrawer` first to create the interface."
7. If exists: Generate `app/Actions/StorageOption/CreateDrawerAction.php`
8. Run `composer lint`
9. Run `/unit-test CreateDrawerAction`

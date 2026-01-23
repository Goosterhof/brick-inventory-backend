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

## Naming Conventions

### Required Verbs
Actions MUST use one of these four verbs: `Create`, `Update`, `Delete`, `Get`

If the user provides a different verb, suggest the appropriate standard verb:
- `Add`, `Assign`, `Register` → suggest `Create`
- `Remove` → suggest `Delete`
- `Fetch` → suggest `Get`

Example response:
> "The verb '{verb}' is not standard. Based on the action's purpose, I suggest using `{suggested}` instead. The action would be named `{Suggested}{Subject}Action`. Proceed?"

### Action Name Format
- Class name: `{Verb}{Subject}Action` (e.g., `CreateStorageOptionAction`)
- File name: `{Verb}{Subject}Action.php`

## Directory Structure

### Infer Subdirectory from Context
Determine the appropriate subdirectory based on the primary model/domain:

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
- Constructor injects the model (for `newInstance()`)
- Execute takes a DTO, returns the created model
- Uses `$this->model->newInstance()` pattern

```php
<?php

declare(strict_types=1);

namespace App\Actions\{Subdomain};

use App\DataTransferObjects\{Create{Subject}Data};
use App\Models\{Subject};

class Create{Subject}Action
{
    public function __construct(
        private readonly {Subject} ${subject},
    ) {}

    public function execute(Create{Subject}Data $data): {Subject}
    {
        ${subject} = $this->{subject}->newInstance();
        // Set properties from $data
        ${subject}->save();

        return ${subject};
    }
}
```

### Update Action (`Update*`)
- NO constructor (model passed to execute)
- Execute takes the model instance + DTO, returns the updated model

```php
<?php

declare(strict_types=1);

namespace App\Actions\{Subdomain};

use App\DataTransferObjects\{Update{Subject}Data};
use App\Models\{Subject};

class Update{Subject}Action
{
    public function execute({Subject} ${subject}, Update{Subject}Data $data): {Subject}
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

namespace App\Actions\{Subdomain};

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
- Constructor injects the model (for query building) and any services
- Execute takes an identifier (string, int), returns the model

```php
<?php

declare(strict_types=1);

namespace App\Actions\{Subdomain};

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

## DTO Handling

### For Create/Update Actions
1. First, check if a fitting DTO already exists in `app/DataTransferObjects/`:
   - `Create{Subject}Data` for Create actions
   - `Update{Subject}Data` for Update actions

2. List existing DTOs:
```bash
ls app/DataTransferObjects/
```

3. If a fitting DTO exists, use it.

4. If NO fitting DTO exists, inform the user:
> "No existing DTO found for this action. Please use the `/dto` skill to create `{Create|Update}{Subject}Data` first, then run this skill again."

Do NOT create DTOs inline - delegate to the `/dto` skill.

## Model Injection Rules

Apply these rules based on how the model is used:

| Usage | Pattern | Reason |
|-------|---------|--------|
| Query building (`newQuery()`, `newInstance()`) | Constructor injection | Easier to mock in tests |
| Modifying specific instance | Execute parameter | Cleaner API, instance already exists |

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
6. Check for existing DTO (Create/Update only)
7. If DTO missing, stop and ask user to create it with `/dto` skill
8. Generate the action file
9. Run `composer lint` to format the code
10. Invoke `/unit-test {ActionName}Action` to generate tests

## Example Workflow

User runs: `/action CreateDrawer`

1. Verb: `Create` (standard, no prompt needed)
2. Subject: `Drawer`
3. Subdirectory: Check if Drawer relates to StorageOption → likely `app/Actions/StorageOption/`
4. Type: Create action template
5. Check DTO: Look for `CreateDrawerData` in `app/DataTransferObjects/`
6. If missing: "No DTO found. Please run `/dto CreateDrawerData` first."
7. If exists: Generate `app/Actions/StorageOption/CreateDrawerAction.php`
8. Run `composer lint`
9. Run `/unit-test CreateDrawerAction`

---
name: controller
description: Create a resource controller for a Laravel model with CRUD operations
argument-hint: ModelName
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(composer lint)
---

# Controller Skill

You are helping create resource controllers for a Laravel API project.

## Arguments

Parse `$ARGUMENTS` to get the Model name (required):

- **`ModelName`** (e.g., `Part`, `StorageOption`): The Eloquent model the controller is for

If no argument is provided, ask the user for the Model name.

## What This Skill Creates

1. A resource controller with CRUD methods: `index`, `store`, `show`, `update`, `destroy`
2. Routes added to `routes/api.php` as explicit individual routes

## Domain Convention

In this codebase, `{Domain}` refers to the subdirectory used to organize related Actions, Contracts, and Requests. The domain typically matches the primary model name (e.g., `StorageOption` model → `StorageOption` domain).

Directory structure:
- `app/Actions/{Domain}/` - Action classes
- `app/Contracts/{Domain}/` - Interface contracts
- `app/Http/Requests/{Domain}/` - Form Requests

## Dependencies Check

Before creating the controller, check if these files exist:

### ResourceData Class
Check if `app/Http/Resources/{ModelName}ResourceData.php` exists:
- If **yes**: Use it in the controller
- If **no**: Inform the user and call the `/resource-data` skill to create it first

### Form Requests
Check if these exist in `app/Http/Requests/{Domain}/`:
- `Store{ModelName}Request.php`
- `Update{ModelName}Request.php`

- If **yes**: Use them in the controller
- If **no**: Inform the user and call the `/form-request` skill to create them first

## Controller Template

Create the controller at `app/Http/Controllers/{ModelName}Controller.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\{Domain}\Create{ModelName}Action;
use App\Actions\{Domain}\Delete{ModelName}Action;
use App\Actions\{Domain}\Get{ModelName}Action;
use App\Actions\{Domain}\Get{ModelPluralName}Action;
use App\Actions\{Domain}\Update{ModelName}Action;
use App\Http\Requests\{Domain}\Store{ModelName}Request;
use App\Http\Requests\{Domain}\Update{ModelName}Request;
use App\Http\Resources\{ModelName}ResourceData;
use App\Models\{ModelName};
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class {ModelName}Controller extends Controller
{
    public function __construct(
        private readonly Get{ModelPluralName}Action $get{ModelPluralName}Action,
        private readonly Get{ModelName}Action $get{ModelName}Action,
        private readonly Create{ModelName}Action $create{ModelName}Action,
        private readonly Update{ModelName}Action $update{ModelName}Action,
        private readonly Delete{ModelName}Action $delete{ModelName}Action,
    ) {}

    /**
     * @return array<int, {ModelName}ResourceData>
     */
    public function index(#[CurrentUser] User $user): array
    {
        ${modelPluralVariable} = $this->get{ModelPluralName}Action->execute($user);

        return {ModelName}ResourceData::collection(${modelPluralVariable});
    }

    public function store(Store{ModelName}Request $request): JsonResponse
    {
        ${modelVariable} = $this->create{ModelName}Action->execute($request);
        ${modelVariable} = $this->get{ModelName}Action->execute(${modelVariable});

        return {ModelName}ResourceData::from(${modelVariable})->toResponseWithStatus(201);
    }

    public function show({ModelName} ${modelVariable}): JsonResponse
    {
        ${modelVariable} = $this->get{ModelName}Action->execute(${modelVariable});

        return {ModelName}ResourceData::from(${modelVariable})->toResponse();
    }

    public function update(Update{ModelName}Request $request, {ModelName} ${modelVariable}): JsonResponse
    {
        ${modelVariable} = $this->update{ModelName}Action->execute(${modelVariable}, $request);
        ${modelVariable} = $this->get{ModelName}Action->execute(${modelVariable});

        return {ModelName}ResourceData::from(${modelVariable})->toResponse();
    }

    public function destroy({ModelName} ${modelVariable}): JsonResponse
    {
        $this->delete{ModelName}Action->execute(${modelVariable});

        return response()->json(null, 204);
    }
}
```

## Route Registration

Add routes to `routes/api.php` inside the `auth:sanctum` middleware group.

Use explicit individual routes (NOT `apiResource`):

```php
use App\Http\Controllers\{ModelName}Controller;

// Inside the auth:sanctum middleware group:
Route::get('{route-name}', [{ModelName}Controller::class, 'index']);
Route::post('{route-name}', [{ModelName}Controller::class, 'store']);
Route::get('{route-name}/{route-parameter}', [{ModelName}Controller::class, 'show']);
Route::put('{route-name}/{route-parameter}', [{ModelName}Controller::class, 'update']);
Route::delete('{route-name}/{route-parameter}', [{ModelName}Controller::class, 'destroy']);
```

### Route Naming Convention
- Route name: kebab-case plural (e.g., `storage-options`, `family-sets`, `parts`)
- Route parameter: snake_case singular (e.g., `storage_option`, `family_set`, `part`)

## Naming Conventions

| Component | Convention | Example for `StorageOption` |
|-----------|------------|----------------------------|
| Domain | `{ModelName}` (typically same as model) | `StorageOption` |
| Controller | `{ModelName}Controller` | `StorageOptionController` |
| ResourceData | `{ModelName}ResourceData` | `StorageOptionResourceData` |
| Store Request | `{Domain}\Store{ModelName}Request` | `StorageOption\StoreStorageOptionRequest` |
| Update Request | `{Domain}\Update{ModelName}Request` | `StorageOption\UpdateStorageOptionRequest` |
| Model variable | `${camelCase}` | `$storageOption` |
| Plural variable | `${camelCasePlural}` | `$storageOptions` |
| Route name | `kebab-case-plural` | `storage-options` |
| Route parameter | `snake_case_singular` | `storage_option` |
| Index Action | `{Domain}\Get{PluralName}Action` | `StorageOption\GetStorageOptionsAction` |
| Show Action | `{Domain}\Get{ModelName}Action` | `StorageOption\GetStorageOptionAction` |
| Store Action | `{Domain}\Create{ModelName}Action` | `StorageOption\CreateStorageOptionAction` |
| Update Action | `{Domain}\Update{ModelName}Action` | `StorageOption\UpdateStorageOptionAction` |
| Destroy Action | `{Domain}\Delete{ModelName}Action` | `StorageOption\DeleteStorageOptionAction` |

## After Creation

1. Run `composer lint` to format the code
2. Inform the user about the Actions that need to be created using the `/action` skill:
   - `Get{PluralName}` (for index)
   - `Get{ModelName}` (for show, and to reload after create/update)
   - `Create{ModelName}` (for store)
   - `Update{ModelName}` (for update)
   - `Delete{ModelName}` (for destroy)
3. Remind about Form Requests that create the interfaces needed for Create/Update actions

## Important Notes

- Do NOT include multi-tenancy checks (`family_id`) - this will be handled by middleware or actions
- Actions are injected via **constructor**, NOT method parameters
- Always use `declare(strict_types=1);`
- The controller extends the base `Controller` class
- Use `#[CurrentUser]` attribute to get the authenticated user when needed
- ResourceData classes have `::from()` and `::collection()` static methods
- Use `->toResponseWithStatus(201)` for created responses

## Return Type Requirements

Controller methods must return explicit types - either `JsonResponse` or `array`:

```php
// Correct - explicit JsonResponse
public function show(Model $model): JsonResponse
{
    return ModelResourceData::from($model)->toResponse();
}

// Correct - array for collections (serialized to JSON by Laravel)
public function index(): array
{
    return ModelResourceData::collection($models);
}

// Wrong - returning ResourceData directly (relies on implicit Responsable conversion)
public function show(Model $model): ModelResourceData  // ❌ Don't do this
{
    return ModelResourceData::from($model);
}
```

## Exception Handling

Controllers should NOT use try-catch blocks. Application exceptions are handled globally in `bootstrap/app.php`. This keeps controllers clean and ensures consistent error responses.

## Route Model Binding

Use Laravel's scoped route model binding for parent-child relationships:

```php
// routes/api.php
Route::delete('/storage-options/{storage_option}/parts/{storage_option_part}', ...)
    ->scopeBindings();  // Automatically verifies child belongs to parent
```

This replaces manual ownership checks. Laravel returns 404 automatically if the child doesn't belong to the parent.

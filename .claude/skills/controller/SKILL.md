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

## Dependencies Check

Before creating the controller, check if these files exist:

### API Resource
Check if `app/Http/Resources/{ModelName}Resource.php` exists:
- If **yes**: Use it in the controller
- If **no**: Inform the user and call the `/resource` skill to create it first

### Form Requests
Check if these exist:
- `app/Http/Requests/Store{ModelName}Request.php`
- `app/Http/Requests/Update{ModelName}Request.php`

- If **yes**: Use them in the controller
- If **no**: Inform the user and call the `/request` skill to create them first

## Controller Template

Create the controller at `app/Http/Controllers/{ModelName}Controller.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\{ModelName}\Index{ModelPluralName}Action;
use App\Actions\{ModelName}\Store{ModelName}Action;
use App\Actions\{ModelName}\Show{ModelName}Action;
use App\Actions\{ModelName}\Update{ModelName}Action;
use App\Actions\{ModelName}\Destroy{ModelName}Action;
use App\Http\Requests\Store{ModelName}Request;
use App\Http\Requests\Update{ModelName}Request;
use App\Http\Resources\{ModelName}Resource;
use App\Models\{ModelName};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class {ModelName}Controller extends Controller
{
    public function index(Index{ModelPluralName}Action $action): AnonymousResourceCollection
    {
        return {ModelName}Resource::collection($action->execute());
    }

    public function store(Store{ModelName}Request $request, Store{ModelName}Action $action): {ModelName}Resource
    {
        // TODO: Create DTO from $request->validated() and pass to action
        $model = $action->execute();

        return new {ModelName}Resource($model);
    }

    public function show({ModelName} ${modelVariable}, Show{ModelName}Action $action): {ModelName}Resource
    {
        return new {ModelName}Resource($action->execute(${modelVariable}));
    }

    public function update(Update{ModelName}Request $request, {ModelName} ${modelVariable}, Update{ModelName}Action $action): {ModelName}Resource
    {
        // TODO: Create DTO from $request->validated() and pass to action
        $model = $action->execute(${modelVariable});

        return new {ModelName}Resource($model);
    }

    public function destroy({ModelName} ${modelVariable}, Destroy{ModelName}Action $action): JsonResponse
    {
        $action->execute(${modelVariable});

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
| Controller | `{ModelName}Controller` | `StorageOptionController` |
| Resource | `{ModelName}Resource` | `StorageOptionResource` |
| Store Request | `Store{ModelName}Request` | `StoreStorageOptionRequest` |
| Update Request | `Update{ModelName}Request` | `UpdateStorageOptionRequest` |
| Model variable | `${camelCase}` | `$storageOption` |
| Route name | `kebab-case-plural` | `storage-options` |
| Route parameter | `snake_case_singular` | `storage_option` |
| Index Action | `Index{PluralName}Action` | `IndexStorageOptionsAction` |
| Other Actions | `{Method}{ModelName}Action` | `StoreStorageOptionAction` |

## After Creation

1. Run `composer lint` to format the code
2. Inform the user about the Actions that need to be created using the `/action` skill:
   - `Index{PluralName}Action`
   - `Store{ModelName}Action`
   - `Show{ModelName}Action`
   - `Update{ModelName}Action`
   - `Destroy{ModelName}Action`
3. Remind about DTOs that may be needed for store/update operations

## Important Notes

- Do NOT include multi-tenancy checks (`family_id`) - this will be handled by middleware
- Actions are injected via method parameters, NOT constructor
- Always use `declare(strict_types=1);`
- The controller extends the base `Controller` class
- Use union return types only when necessary (e.g., `JsonResponse` for errors)

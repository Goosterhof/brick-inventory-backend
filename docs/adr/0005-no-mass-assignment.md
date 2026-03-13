# ADR-0005: No Mass Assignment

**Status:** Accepted

## Context

Laravel provides `$fillable` and `$guarded` for mass assignment protection. This project assigns model properties in Action classes.

## Decision

**No `$fillable` or `$guarded` on models.** Assign every property explicitly in Action classes:

```php
$model = $this->model->newInstance();
$model->family_id = $this->user->family_id;
$model->name = $data->name;
$model->save();
```

Exception: `User` model keeps `protected $guarded = ['password']` for security.

## Alternatives Considered

- **Mass assignment with `$fillable`** — convenient but hides which properties are set. Easy to accidentally expose fields. Breaks down when different actions need different assignable fields.

## Consequences

- Every property assignment is visible and auditable
- No `Model::create()` or `$model->fill()` calls
- More explicit, slightly more verbose

## Enforced By

- `tests/Architecture/ModelArchitectureTest.php` — no `$fillable`, no `$guarded`

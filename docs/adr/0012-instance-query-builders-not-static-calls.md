# Decision: Instance Query Builders, Not Static Model Calls

**Date**: 2026-03-22
**Feature**: Dependency injection purity in Actions
**Status**: accepted
**Transferability**: project-specific

## Context

Laravel conventionally uses static methods on models for queries: `User::where(...)`, `Set::find(...)`. This works, but it creates a hidden dependency — the Action is coupled to a concrete class at the call site, and that static call cannot be intercepted by dependency injection.

In this project, Actions receive model instances via constructor injection. The question: should those injected instances be used via `$this->model::where()` (static-through-instance) or `$this->model->newQuery()->where()` (instance method)?

Static-through-instance looks like DI but isn't — PHP resolves the class statically, bypassing the injected instance entirely. This defeats the purpose of injection and makes the Action harder to test.

## Options Considered

| Option | Pros | Cons | Why eliminated / Why chosen |
|--------|------|------|-----------------------------|
| **`newQuery()`/`newInstance()` on injected models** | True DI — queries go through the injected instance; testable; explicit | Unfamiliar to Laravel developers; slightly more verbose | **Chosen** — correctness over convention |
| **Static-through-instance (`$this->model::where()`)** | Looks like DI; familiar syntax | Resolved statically by PHP; the injected instance is irrelevant; misleading | Eliminated — it's a lie |
| **Static calls directly (`Model::where()`)** | Idiomatic Laravel | No DI at all; Actions become untestable without database | Eliminated — incompatible with the testing strategy |
| **Repository pattern wrapping Eloquent** | Full abstraction over queries | Heavy overhead; Eloquent is already expressive enough at this scale | Eliminated — over-engineering |

## Decision

Actions must use `$this->model->newQuery()` for queries and `$this->model->newInstance()` for creating new records. Direct static calls (`Model::where()`) and static-through-instance calls (`$this->model::where()`) are both prohibited.

```php
// Query building — through the injected instance
$set = $this->set->newQuery()->where('set_num', $setNum)->first();

// Instance creation — through the injected instance
$storageOption = $this->storageOption->newInstance();
$storageOption->name = $data->name;
$storageOption->save();
```

The architecture test uses a regex (`/\$this->\w+::\w+\(/`) to detect static-through-instance violations.

## Consequences

- All model interactions flow through injected instances — true dependency injection
- Actions are testable by injecting model instances with controlled state
- Unfamiliar to developers coming from standard Laravel — requires onboarding
- `newQuery()` and `newInstance()` are standard Eloquent methods, just rarely used directly

## Enforcement

| What | Mechanism | Scope |
|------|-----------|-------|
| No static-through-instance calls | `ActionArchitectureTest` (regex scan) | `app/Actions/` |
| No direct static model calls | `ActionArchitectureTest` + Deptrac (Actions depend on Model instances, not static facades) | `app/Actions/` |

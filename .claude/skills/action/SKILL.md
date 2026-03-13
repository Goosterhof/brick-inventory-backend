---
name: action
description: Create Action classes following project conventions
argument-hint: <ActionName>
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(composer lint)
---

# Action Skill

Create Action classes. Parse `$ARGUMENTS` for the action name (without `Action` suffix).

## Naming

### Standard Verbs: `Create`, `Update`, `Delete`, `Get`

Suggest standard verbs for synonyms:
- `Store`, `Add`, `Register` → `Create`
- `Destroy`, `Remove` → `Delete`
- `Show`, `Index`, `Fetch`, `List` → `Get`

### Extended Verbs (when standard verbs don't fit)

| Verb | Use When |
|------|----------|
| `Upsert` | Create-or-update based on identifier |
| `Assign` | Linking/associating records with upsert semantics |
| `Store` | Bulk persistence of related data |

**Naming accuracy over consistency** — a misleading name causes more confusion than a non-standard verb.

### Format: `{Verb}{Subject}Action` in `app/Actions/{Domain}/`

Domain = subdirectory matching the primary model (e.g., `StorageOption`). Check `ls app/Actions/` first.

## Action Patterns by Verb

Read existing actions for templates. Key differences:

| Verb | Constructor | Execute signature |
|------|-------------|-------------------|
| `Create` | Injects model (for `newInstance()`) + `#[CurrentUser]` | `(CreateInterface $data): Model` |
| `Update` | None | `(Model $model, UpdateInterface $data): Model` |
| `Delete` | None | `(Model $model): void` |
| `Get` (by ID) | Injects model (for `newQuery()`) | `(string $id): Model` |
| `Get` (load relations) | None | `(Model $model): Model` |
| `Get` (collection) | Injects model (for `newQuery()`) | `(User $user): Collection` |

## Interface Handling

Create/Update actions accept an interface, not a concrete request.

1. Check `app/Contracts/{Domain}/` for existing interface
2. If missing → stop and tell user to run `/form-request` first (which creates the interface)
3. Never create interfaces inline

## Delegation Pattern

Before creating, check `ls app/Actions/{Domain}/` for existing actions. **Delegate, don't duplicate:**
- `Get*Action` needs creation logic → delegate to `Upsert*Action`
- `Create*Action` with update logic → rename to `Upsert*` or `Assign*`

## After Creation

1. Run `composer lint`
2. Invoke `/unit-test {ActionName}Action`

---
name: migration
description: Generate an Eloquent migration from a model name
argument-hint: <ModelName>
allowed-tools: Read, Grep, Glob, Write, Edit, Bash(composer lint), Skill
---

# Migration Skill

Create migrations. Parse `$ARGUMENTS` for the model name (PascalCase → snake_case table).

## Auto-Detection

1. **Pivot table?** — If name combines two existing models (e.g., `ColorPart` → `Color` + `Part`), create pivot with both FKs + unique constraint
2. **Existing migration?** — If `create_{table}_table` exists, create a modify migration instead

## Workflow

1. Detect type (pivot / create / modify)
2. Ask user for columns (plain English)
3. Validate foreign key targets exist (stop if migration missing)
4. Infer tenant scoping — include `family_id` for user-owned data, exclude for reference data (see `/conventions`)
5. Generate file: `{date}_create_{table}_table.php` or `{date}_add_{cols}_to_{table}_table.php`
6. Run `composer lint`
7. Auto-invoke `/model {ModelName}` (ask first if model already exists)

Read existing migrations for the template pattern.

## Critical Rules

- **NO cascade deletes** — never `->onDelete('cascade')` or `->cascadeOnDelete()`. Deletion logic belongs in Actions.
- Anonymous class syntax: `return new class extends Migration`
- `void` return types on `up()`, `down()`, and Schema callbacks
- Foreign keys: `$table->foreignId('x_id')->constrained()` (nullable: add `->nullable()` before `->constrained()`)

# Project Instructions

## Code Quality

After making any changes to PHP files, always run:

```bash
composer lint
```

This runs Rector (refactoring) followed by Pint (formatting) to ensure code quality.

## Before Committing

Before creating any commit, always:

1. Run `composer lint` to fix code style
2. Run `composer test` to ensure all tests pass

Both must pass before committing. If tests fail, fix them before proceeding.

## Tech Stack

- Laravel 12 (API-only, no frontend)
- PHP 8.4+
- SQLite for local development

## Commands

| Command | Description |
|---------|-------------|
| `composer dev` | Start development server |
| `composer test` | Run tests |
| `composer lint` | Run Rector + Pint (fix mode) |
| `composer lint:test` | Run Rector + Pint (dry-run) |

# Project Instructions

## Code Quality

After making any changes to PHP files, always run:

```bash
composer lint
```

This runs Rector (refactoring) followed by Pint (formatting) to ensure code quality.

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

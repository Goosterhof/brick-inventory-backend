# ADR-0007: #[Config] Attributes, Not Helpers or Facades

**Status:** Accepted

## Context

Laravel provides three ways to access configuration: `config()` helper, `Config` facade, and PHP 8 `#[Config]` attribute injection. This project also bans facades in application code.

## Decision

Use `#[Config]` attributes for all configuration access in application code:

```php
public function __construct(
    #[Config('services.rebrickable.key', '')] private string $apiKey,
    #[Config('services.rebrickable.base_url', '...')] private string $baseUrl,
) {}
```

No `config()` helper, no `Config` facade, no `env()` outside config files.

## Alternatives Considered

- **`config()` helper** — hides dependencies, not visible in constructor signature, harder to test.
- **`Config` facade** — same issues plus facade overhead.
- **`env()` in application code** — breaks config caching, violates twelve-factor app principles.

## Consequences

- All configuration dependencies are explicit in the constructor
- Easy to override in tests via container
- Providers are exempt (they wire things at boot time)

## Enforced By

- `tests/Architecture/ConfigArchitectureTest.php` — no `config()`, no `Config` facade
- `tests/Architecture/GeneralArchitectureTest.php` — no `env()`, no facades

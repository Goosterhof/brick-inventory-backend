# ADR-0001: Session-Based SPA Auth, Not Tokens

**Status:** Accepted

## Context

Laravel Sanctum supports two modes: stateless bearer tokens and stateful session-based auth. The frontend is a single SPA that communicates with this API.

## Decision

Use **session-based SPA authentication**. No tokens are created or stored.

- `Auth::login($user)` creates a session on login/register
- `Auth::guard('web')->logout()` + session invalidation on logout
- `SANCTUM_STATEFUL_DOMAINS` includes the frontend's `host:port`
- CSRF excluded for `api/*` routes (frontend doesn't fetch CSRF cookies)

## Alternatives Considered

- **Bearer tokens** — adds token storage, rotation, and revocation complexity for no benefit when the consumer is a single SPA on a known domain.

## Consequences

- Feature tests use `$this->actingAs($user)`, not `Sanctum::actingAs()`
- Controllers return `ProfileResourceData` directly (no `{user, token}` wrapper)
- `$request->session()` throws without session middleware — guard with `$request->hasSession()`

## Enforced By

- `config/sanctum.php` — `guard` set to `['web']`
- `bootstrap/app.php` — CSRF excluded for `api/*`

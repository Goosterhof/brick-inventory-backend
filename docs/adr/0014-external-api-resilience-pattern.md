# Decision: External API Resilience Pattern

**Date**: 2026-03-22
**Feature**: Reliable integration with Rebrickable and Brickognize APIs
**Status**: accepted
**Transferability**: universal

## Context

The system depends on two external APIs (Rebrickable for LEGO data, Brickognize for image recognition). External APIs fail in predictable ways: timeouts, rate limits, malformed responses, transient network errors. Without a consistent resilience strategy, each service handles failures differently, making behavior unpredictable and debugging painful.

The question: what does a standardized external API integration look like in this codebase?

## Options Considered

| Option | Pros | Cons | Why eliminated / Why chosen |
|--------|------|------|-----------------------------|
| **Standardized httpClient() with timeout, retry, and explicit error handling** | Consistent across services; testable; failures are typed | Slightly more boilerplate per service; retry config is hardcoded | **Chosen** — predictability over flexibility |
| **Laravel's HTTP client defaults** | Zero configuration | 30s timeout but no retry; no structured error handling; exceptions are generic | Eliminated — insufficient for production reliability |
| **Circuit breaker pattern (e.g., via package)** | Prevents cascading failures under sustained outages | Over-engineering at current scale (2 services, moderate traffic) | Eliminated — complexity not justified yet |
| **Queue-based async calls** | Decouples API latency from request lifecycle | Many operations need synchronous results (e.g., search, identification); adds infrastructure complexity | Eliminated — doesn't fit the synchronous use cases |

## Decision

Every Service builds its HTTP client through a private `httpClient()` method with standardized resilience settings:

```php
private function httpClient(): PendingRequest
{
    return $this->httpFactory->baseUrl($this->baseUrl)
        ->withHeaders([...])
        ->acceptJson()
        ->timeout(30)
        ->retry(3, 100, throw: false);
}
```

**The standard settings:**
- **30-second timeout** — long enough for slow API responses, short enough to not hang requests
- **3 retries with 100ms delay** — handles transient failures without hammering the API
- **`throw: false`** — responses are handled explicitly, not via exception-on-failure

**Error handling follows a typed exception hierarchy:**

```
ExternalApiException (abstract base)
├── RebrickableApiException
│   └── SetNotFoundException (404 specialization)
├── BrickognizeApiException
└── InvalidApiResponseException (malformed response)
```

Each exception captures the HTTP status code and response body. The global exception handler maps these to appropriate HTTP responses (502 for upstream failures, 404 for not-found resources).

**Response validation** is explicit — Services check for required fields and valid structure before returning data, throwing `InvalidApiResponseException` for malformed responses.

## Consequences

- Every external API call has identical resilience behavior — no service is more fragile than another
- Typed exceptions allow the global handler to return meaningful HTTP status codes to clients
- `throw: false` means every Service must explicitly check `$response->failed()` — cannot accidentally ignore failures
- Retry config is hardcoded; if different APIs need different settings, the pattern needs parameterization
- Services are tested with `Http::fake()` — resilience behavior is verifiable without hitting real APIs

## Enforcement

| What | Mechanism | Scope |
|------|-----------|-------|
| Services use `Http::fake()` in tests | `ServiceArchitectureTest` (no real HTTP calls) | `tests/Unit/Services/` |
| Services implement contracts | `ServiceArchitectureTest` | `app/Services/` |
| Exception hierarchy with status codes | Global exception handler in `bootstrap/app.php` | `app/Exceptions/` |

## Open Questions

- Should retry count and delay be configurable via `#[Config]` attributes instead of hardcoded? Current values work for both APIs, but a third integration with different rate limits might need flexibility.
- Should `InvalidApiResponseException` log the malformed response body for debugging? Currently it captures the message but not the raw response.

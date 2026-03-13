# Architecture Decision Records

Decisions that shaped the build. Each records what was chosen, what was rejected, and what enforces it.

| ADR | Decision | Enforced By |
|-----|----------|-------------|
| [0001](0001-session-auth-not-tokens.md) | Session-based SPA auth, not tokens | Sanctum config, bootstrap/app.php |
| [0002](0002-single-tier-authorization.md) | Single-tier authorization with three-layer defense | PolicyArchitectureTest, RoutingArchitectureTest |
| [0003](0003-actions-and-services-separation.md) | Actions for business logic, Services for HTTP only | ActionArchitectureTest, ServiceArchitectureTest, Deptrac |
| [0004](0004-explicit-cascade-deletion.md) | Explicit cascade deletion, not database-level | MigrationArchitectureTest, CascadeRelationArchitectureTest |
| [0005](0005-no-mass-assignment.md) | No mass assignment ($fillable/$guarded) | ModelArchitectureTest |
| [0006](0006-dto-form-requests-and-resource-data.md) | DTOFormRequest + custom ResourceData | RequestArchitectureTest, ResourceDataArchitectureTest |
| [0007](0007-config-attributes-not-helpers.md) | #[Config] attributes, not helpers/facades | ConfigArchitectureTest, GeneralArchitectureTest |
| [0008](0008-explicit-routes-not-api-resource.md) | Explicit routes, not apiResource | RoutingArchitectureTest |
| [0009](0009-thin-controllers-method-injection.md) | Thin controllers with method injection only | ControllerArchitectureTest |

## Adding a New ADR

1. Copy the format from any existing ADR
2. Number sequentially: `NNNN-short-title.md`
3. Include: Status, Context, Decision, Alternatives Considered, Consequences, Enforced By
4. Update this index

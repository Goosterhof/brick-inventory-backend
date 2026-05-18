# brick-inventory-backend — ARCHIVED

> **This repository has been merged into the monorepo at [Goosterhof/brick-inventory-orchestrator](https://github.com/Goosterhof/brick-inventory-orchestrator) under `backend/`.**

As of 2026-05-17, all active development of the Brick Inventory backend (Laravel 12 API) lives in the orchestrator monorepo. This standalone repository is preserved for historical reference and is no longer maintained.

## Why a monorepo

The orchestrator now ships both backend and frontend as a **single Railway service** — a multi-stage Dockerfile builds the Vue apps, overlays their dists onto `backend/public/`, and FrankenPHP serves both surfaces from the same origin. This removes the cross-port CORS/Sanctum complexity the standalone repos had to work around and simplifies deployment to a single image.

## Where to find things

- **Active development:** [`Goosterhof/brick-inventory-orchestrator`](https://github.com/Goosterhof/brick-inventory-orchestrator), in the `backend/` subdirectory.
- **Final standalone state:** tagged [`pre-monorepo-merge-2026-05-17`](https://github.com/Goosterhof/brick-inventory-backend/releases/tag/pre-monorepo-merge-2026-05-17) on this repo (commit `1f1d30d`).
- **Pre-merge history:** preserved in the monorepo via `git subtree add` — `git log --follow` against any `backend/<path>` file in the orchestrator reaches commits that originated here.

## Issues and PRs

Please file new issues and pull requests against the orchestrator. This repository is archived; issues filed here will not be reviewed.

---
trigger: always_on
---

# Engineering Rules (joranski/agents)

## Stack
- UI is **Laravel + Livewire 4 + FluxUI Pro + Tailwind CSS**. Use `<flux:*>` Pro components before custom markup or Alpine.
- **Zero FilamentPHP:** never install, scaffold, or suggest Filament resources, pages, panels, or plugins. Where legacy Filament exists, it is maintenance-only; new screens are Livewire + Flux.
- Laravel Boost owns framework guidance (`laravel-best-practices`, `livewire-development`, `fluxui-development`, `tailwindcss-development`, testing). Use its `search-docs` for version-specific APIs.

## Package boundaries
- Code under `packages/` (`joranski/*`) never imports `App\*`, especially `App\Models\*`. The host binds interfaces or passes DTOs.
- Host-specific behavior goes in the host app, not in a package.

## Discipline
- Tests first for behavior changes (`.agents/skills/test-driven-development/SKILL.md`).
- No "done", "fixed", or "passing" without fresh evidence (`.agents/skills/verification-before-completion/SKILL.md`).
- Iterate with surgical test runs only (`.agents/skills/_shared/test-runner.md`); the full suite is for completion and pre-push.

## Database safety
- Never run `migrate:fresh`, `migrate:reset`, `db:wipe`, `db:seed` against shared, staging, or production databases, or destructive SQL (`DROP`, `TRUNCATE`, unscoped `DELETE`/`UPDATE`) anywhere, without explicit approval in this conversation.
- Schema changes go through new migrations; never edit a migration that has already run in shared environments.

## Git safety
- No force-push to `main`/`master`, no `--no-verify`, no history rewrites on shared branches.
- Never commit secrets, `.env`, or `auth.json`. Commit or push only when asked.

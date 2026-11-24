# Credits & Attribution

This package stands on the shoulders of giants. The skills, patterns, and conventions bundled here are derived from or inspired by several excellent upstream projects, all of whom deserve credit.

## Workflow Skills (Superpowers)

The following workflow skills are derived from or inspired by Jesse Vincent's [Superpowers](https://github.com/jessesquires/superpowers) project, which pioneered the modular `SKILL.md` format and the brainstorm → plan → execute → review pipeline:

- `writing-plans`
- `writing-skills`
- `executing-plans` (including the review-per-task prompts from the archived `single-flow-task-execution`)
- `systematic-debugging`
- `test-driven-development`
- `verification-before-completion`
- `code-review`
- Optional: `using-git-worktrees`, `removing-dead-files`, `git-push`
- Archived: `brainstorming`, `single-flow-task-execution`, `finishing-a-development-branch`

These skills retain Superpowers' core philosophy — pressure-resistant language, anti-pattern sections, RED-GREEN-REFACTOR mapping, and bulletproofed checklists — adapted for Laravel + PHP workflows and tool-agnostic execution. License: MIT (Superpowers).

## Laravel Boost

Framework skills (`laravel-best-practices`, `testing-best-practices`, `livewire-development`, `fluxui-development`, `tailwindcss-development`) are not bundled here — [Laravel Boost](https://github.com/laravel/boost) installs the canonical versions, and `bin/sync-agents` never touches them. The `ui-design.mdc` templates build on Boost's `fluxui-development`. License: MIT (Laravel).

## Anthropic-Canonical Skills

The following optional skills are inspired by Anthropic's official skill catalog. They ship marked `canonical: false` and `bundled_by: joranski/agents` so the upstream canonical version (when available via `@anthropics/skills` or similar) takes precedence:

- `webapp-testing`
- `mcp-builder`

Source: [Anthropic Official Skills](https://officialskills.sh/anthropics). License: MIT (Anthropic).

## Sentry-Canonical Skills

The following optional skill is inspired by Sentry's [agent-skills](https://github.com/getsentry) catalog. It ships marked `canonical: false` and `bundled_by: joranski/agents`:

- `agents-md`

Source: [Sentry Agent Skills](https://officialskills.sh/getsentry). License: MIT (Sentry).

## Original Work

The following are original to this package and not derived from external sources:

- `package-extraction-scout` — heuristic-driven analysis for identifying Composer-package extraction candidates
- `harden-logic`, `grill-me`, `simplify-pass`, `second-opinion-review`
- `bin/sync-agents` and `src/Sync/AgentSync.php` — the manifest-tracked, idempotent sync engine used by `agents:install`
- The `templates/` (thin `AGENTS.md`, `CLAUDE.md`, `ui-design.mdc` profiles)
- `git:pull` — Laravel-aware smart deploy pipeline
- `git:push` — quality-gated push command
- The Night Shift automation (`stubs/bin/night-shift`)
- The Laravel-specific framing, MCP configuration stubs (`stubs/mcp/`), and the `rules/` content

## License

This package is MIT-licensed. All upstream sources listed above are also MIT-licensed (or compatible). See [LICENSE](LICENSE) for details.

## Reporting Attribution Issues

If you maintain one of the upstream projects above and feel the attribution here is incomplete, incorrect, or insufficient, please open an issue at <https://github.com/joranski/agents/issues> and we will fix it promptly.

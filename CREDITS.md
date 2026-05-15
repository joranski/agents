# Credits & Attribution

This package stands on the shoulders of giants. The skills, patterns, and conventions bundled here are derived from or inspired by several excellent upstream projects, all of whom deserve credit.

## Workflow Skills (Superpowers)

The following workflow skills are derived from or inspired by Jesse Vincent's [Superpowers](https://github.com/jessesquires/superpowers) project, which pioneered the modular `SKILL.md` format and the brainstorm → plan → execute → review pipeline:

- `brainstorming`
- `writing-plans`
- `writing-skills`
- `executing-plans`
- `single-flow-task-execution`
- `using-superpowers`
- `using-git-worktrees`
- `systematic-debugging`
- `test-driven-development`
- `verification-before-completion`
- `requesting-code-review`
- `receiving-code-review`
- `removing-dead-files`
- `finishing-a-development-branch`
- `blueprint-code-review`
- `git-push`

These skills retain Superpowers' core philosophy — pressure-resistant language, anti-pattern sections, RED-GREEN-REFACTOR mapping, and bulletproofed checklists — adapted for Laravel + PHP workflows and tool-agnostic execution. License: MIT (Superpowers).

## Laravel-Canonical Skills

The following skills are bundled copies of skills published by the Laravel team. They ship marked `canonical: false` and `bundled_by: joranski/agents` so that if Laravel publishes its own canonical version (e.g. via Boost or `laravel/skills`), that version takes precedence on `php artisan agents:install`:

- `laravel-best-practices` (and all rules under `rules/`)
- `pest-testing`
- `fluxui-development`
- `volt-development`
- `tailwindcss-development`

Source: [Laravel Skills](https://laravel.com) / [Laravel Boost](https://github.com/laravel/boost). License: MIT (Laravel).

## Anthropic-Canonical Skills

The following skills are inspired by Anthropic's official skill catalog. They ship marked `canonical: false` and `bundled_by: joranski/agents` so the upstream canonical version (when available via `@anthropics/skills` or similar) takes precedence:

- `webapp-testing`
- `mcp-builder`

Source: [Anthropic Official Skills](https://officialskills.sh/anthropics). License: MIT (Anthropic).

## Sentry-Canonical Skills

The following skill is inspired by Sentry's [agent-skills](https://github.com/getsentry) catalog. It ships marked `canonical: false` and `bundled_by: joranski/agents`:

- `agents-md`

Source: [Sentry Agent Skills](https://officialskills.sh/getsentry). License: MIT (Sentry).

## Original Work

The following are original to this package and not derived from external sources:

- `package-extraction-scout` — heuristic-driven analysis for identifying Composer-package extraction candidates
- The `agents:install` command's collision-safe installer (manifest tracking, ownership-aware decision matrix, `--force` / `--force-all` semantics)
- `git:pull` — Laravel-aware smart deploy pipeline
- `git:push` — quality-gated push command
- The Night Shift automation (`stubs/bin/night-shift`)
- The Laravel-specific framing, MCP configuration stubs (`stubs/mcp/`), and the `agents/rules/` content

## License

This package is MIT-licensed. All upstream sources listed above are also MIT-licensed (or compatible). See [LICENSE](LICENSE) for details.

## Reporting Attribution Issues

If you maintain one of the upstream projects above and feel the attribution here is incomplete, incorrect, or insufficient, please open an issue at <https://github.com/joranski/agents/issues> and we will fix it promptly.

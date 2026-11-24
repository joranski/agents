# Changelog

## 3.0.0

Breaking: a lean core, a standalone sync engine, and Zero FilamentPHP everywhere.

- **Lean core: 12 active skills.** `writing-plans`, `executing-plans`, `test-driven-development`, `systematic-debugging`, `verification-before-completion`, `code-review`, `package-extraction-scout`, `writing-skills`, `harden-logic`, plus the new `grill-me`, `simplify-pass`, and `second-opinion-review`. Every description says when to use the skill and when not to.
- **`optional/` skills are opt-in** with `--with=`: `git-push`, `night-shift`, `removing-dead-files` (all `disable-model-invocation: true`), `agents-md`, `using-git-worktrees`, `react-development`, `webapp-testing`, `mcp-builder`, `web-compliance-audit`.
- **Archived** (kept in `archive/`, never synced, excluded from Composer installs): `brainstorming`, `single-flow-task-execution` (its review prompts moved into `executing-plans`), `finishing-a-development-branch`, `system-cli-and-bash`, `voice-spec-ingestor`, `ai-media-pipeline`, `auto-research-optimizer`, `taste-differential-ui`, `flux-admin-crud`, `polyglot-architecture`. See [archive/README.md](archive/README.md).
- **`rules/guidelines.md` → `rules/engineering.md`.** Untouched copies of `guidelines.md` are pruned on sync, so hosts keep one always-on rule file. Edited copies are kept with a notice.
- **UI guidance moves into a host template:** `templates/cursor/ui-design.{storefront-admin,admin-only}.mdc` (`alwaysApply: false`, glob-scoped). It absorbs the `flux-admin-crud` CRUD pattern. Volt and React are no longer defaults.
- **New `bin/sync-agents`** (framework-free PHP, also `vendor/bin/sync-agents`). It is idempotent, supports `--dry-run`, and is tracked by the manifest in `.agents/.joranski-agents-manifest.json`. It prunes skills the package no longer ships, recognizes untouched copies from any earlier release via `known-hashes.json` (even with no manifest), imports the legacy `.agents/.manifest.json`, and mirrors into a real `.claude/skills` directory. It never touches Boost skills, `AGENTS.md`, `CLAUDE.md`, `mcp.json`, or other `.cursor/rules/*.mdc` files. `--init --profile=` scaffolds a thin `AGENTS.md`, a one-line `CLAUDE.md`, and the ui rule in new repos.
- **`agents:install` / `agents:update` delegate skills and rules to the sync engine.** New options: `--with=`, `--ui-rule`, `--profile=`. The old retired-skill lists are gone; the manifest and known hashes replace them.
- `harden-logic` is slimmed to a ~45-line `SKILL.md` plus `reference.md`. Its TypeScript and Rust assets are archived.

## 2.4.2

- **FluxUI primary default, React specialized track.** The frontend rule moves from "co-equal stacks" to a hierarchy: Livewire + Volt + FluxUI for all Laravel web UI by default; React only when the project already uses it, the user asks for React / Next.js / React Native, or a feature needs heavy client-side state. Updated in `rules/guidelines.md`, `agents-md`, `polyglot-architecture`, and `react-development`. Zero FilamentPHP is unchanged.

## 2.4.1

- **Testing override.** `rules/guidelines.md` and the `agents-md` skill forbid full-suite runs mid-iteration in favor of `_shared/test-runner.md` (`pest --dirty --stop-on-failure --compact`), since Boost's testing skill defaults to the full suite.
- **Leaner `systematic-debugging` and `writing-skills`.** Both follow the `SKILL.md` (<40 lines) + `examples.md` + `assets/` layout. `systematic-debugging` starts from `storage/logs/laravel.log` and the `chrome-devtools` MCP; its meta-test files and graphviz tooling are gone. Retired files are pruned from existing projects on `agents:update` (edited copies are kept with a notice).
- **Terminal bell.** `git:push` and `bin/night-shift` ring the bell on exit when attached to a terminal, so the tab badges when a background run finishes.
- `bin/night-shift`: a failing `composer audit` no longer aborts the script under `set -e` before the vulnerability check runs.

## 2.4.0

- **More shared MCP servers.** `context7` is added everywhere; `chrome-devtools` for React / Vue / Svelte front ends; `shadcn` when `components.json` exists; `next-devtools` for Next.js 16+. Same merge rules as v2.3.0: missing servers are added, existing entries are never overwritten.

## 2.3.0

- **One skills directory.** `.agents/skills/` holds every skill (ours and Boost's). Boost's Cursor skills are redirected there, `.claude/skills` becomes a symlink to it, and existing `.cursor/skills` / `.claude/skills` folders are folded in on upgrade (user-authored skills are moved, never deleted).
- **Shared MCP servers.** `laravel-boost` and, for Flux projects, `fluxui-server` are merged into the Claude Code, Cursor, Gemini (and existing VS Code / Antigravity) MCP configs. Replaces the published `.gemini/settings.json` stub.
- **`agents:install` runs `boost:update`** at the end (`--no-boost` to skip), so a single command rebuilds `.agents`, `.claude`, `.cursor` skills and `.gemini` from scratch. `agents:update` delegates to it.
- `*filament*` pruning and the retired-skill cleanup no longer touch Boost's skills now that they share `.agents/skills/`.

## 2.2.1

- `agents:update` no longer asks "Run credentials & environment setup?" — it passes the new `agents:install --no-setup`, so it runs cleanly from Composer scripts and cron.

## 2.2.0

- **Laravel Boost owns the framework skills.** Removed the bundled copies of `laravel-best-practices`, `fluxui-development`, `tailwindcss-development`, `volt-development` and `pest-testing`. They were overridden by Boost's copies in Cursor and Claude Code anyway. On upgrade, `agents:install` deletes untouched copies from `.agents/skills/`; copies you edited are kept and listed in the notices.
- **New `flux-admin-crud` skill** carries the Zero FilamentPHP admin-screen pattern, the Filament replacement guide, and `flux-crud-stub.blade.php` that used to live in the bundled `fluxui-development`.
- **Boost is part of setup.** `agents:install` offers to run `boost:install` when `boost.json` is missing; `agents:update` finishes with `boost:update` (skip with `--no-boost`).
- The surgical-testing Iron Law lives in `_shared/test-runner.md`; skills now point to Boost's `testing-best-practices` for Pest patterns.
- **Claude Code sees the skills.** Skills are mirrored to `.claude/skills/` when the project uses Claude Code.
- **Dropped the `claude.json` stub.** It pointed at a non-existent `php artisan mcp` command and Claude Code reads `.mcp.json` (written by Boost). Untouched copies are removed on upgrade.
- **`.gitignore` allowlist for `.agents/`** keeps agent scratch directories out of git; only `rules/` and `skills/` are committed.
- **Safer `*filament*` pruning.** Outside `.agents/skills/`, only skills this package installed are removed.

## 2.1.0 & 2.1.1

- **Automatic pruning in `agents:install` (2.1.1):** removes obsolete v1 skill directories (`using-superpowers`, `requesting-code-review`, `receiving-code-review`, `blueprint-code-review`, and `*filament*`) and strips legacy `filament-examples` MCP entries while preserving custom servers.
- **New `php artisan agents:update`:** checks composer constraints, runs `composer update joranski/agents`, and force-refreshes installed stubs with automatic pruning.
- **React & FluxUI co-equal parity (2.1.0):** `react-development`, `react-crud-stub.tsx`, auto-detection of the frontend stack, and `taste-differential-ui` support for both stacks.
- **Dormant `web-compliance-audit` gate (2.1.0):** ADA Title III / WCAG 2.2 AA, privacy and Global Privacy Control, Prop 65 and FTC click-to-cancel, with copy-paste snippets.

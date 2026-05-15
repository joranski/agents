---
name: agents-md
description: "Generate, audit, and maintain AGENTS.md files at the repository root. Use when starting a new project, when an existing AGENTS.md is missing/stale, or when reviewing whether the AGENTS.md actually reflects the codebase. AGENTS.md is the canonical entrypoint that AI coding agents read first to understand a project's stack, conventions, commands, and rules."
license: MIT
source: getsentry/skills
canonical: false
bundled_by: joranski/agents
metadata:
  author: sentry
  upstream: https://officialskills.sh/getsentry/skills/agents-md
---

# AGENTS.md

## Overview

`AGENTS.md` is the project-root file that AI coding agents (Cursor, Claude Code, Codex, etc.) read first when entering a repository. A good `AGENTS.md` reduces wasted exploration time, prevents the agent from inventing wrong conventions, and tells humans + agents where the project stores its non-obvious knowledge.

This skill helps you:
1. Generate a fresh `AGENTS.md` from a real codebase
2. Audit an existing `AGENTS.md` against current reality
3. Keep `AGENTS.md` *short* — it loads into every agent context

## When to Use

- New project — no `AGENTS.md` exists
- Existing `AGENTS.md` references commands/files that no longer exist
- Stack changed (e.g. swapped from Vite → Bun, or PHPUnit → Pest)
- Onboarding a new agent (Codex, Cursor, etc.) and the existing file is Claude-only
- Skill review identifies that agents keep getting the same thing wrong

## When NOT to Use

- Per-tool config (`.cursor/rules/`, `CLAUDE.md`) — those are tool-specific, `AGENTS.md` is the shared root
- Long-form architecture docs — those belong in `docs/`, not `AGENTS.md`
- Skill instructions — those belong in `.agents/skills/<name>/SKILL.md`

## The Iron Law

**`AGENTS.md` must be readable in under 60 seconds.** Every line costs context tokens on every agent invocation. If something needs more depth, link to a doc — don't inline it.

Target: **150–300 lines max.** Anything longer is failure.

## Required Sections

Every well-formed `AGENTS.md` has these sections in order:

### 1. Project Identity
- One-sentence description
- Primary language + framework versions (PHP 8.4 / Laravel 12, etc.)
- Production URL (if applicable)

### 2. Stack
- Runtime (PHP 8.4, Node 22, etc.)
- Framework (Laravel 12, Filament 4, Livewire 4, Volt 1, Flux UI 2)
- Database (Postgres 16, MySQL 8.4, etc.)
- Frontend build (Vite, Bun, Tailwind v4)
- Testing (Pest 4, Vitest, Playwright)
- Deployment target (Forge, Vapor, Cloud, custom Linux)

### 3. Setup
The exact commands a fresh checkout needs:
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
```

### 4. Daily Commands
What developers (and agents) run constantly:
```bash
php artisan test                      # Run all tests
php artisan test --filter=AuthTest    # Single test
vendor/bin/pint                       # Format code
php artisan tinker                    # REPL
```

### 5. Conventions
The non-obvious rules that aren't enforced by tooling:
- "All routes go in `routes/web.php`, never in controllers"
- "Use Volt SFC for all new Livewire components"
- "Form requests live in `app/Http/Requests/`, one per action"
- "Database operations always go through Eloquent — no raw SQL except in migrations"

### 6. Skills & Rules Pointers
Link, don't inline:
```markdown
## Agent Skills

This project uses [joranski/agents](https://github.com/joranski/agents) for AI agent skills.
Skills live in `.agents/skills/`. Project-specific rules are in `.agents/rules/`.

Key skills to invoke:
- Before any feature → `.agents/skills/brainstorming/SKILL.md`
- Before writing tests → `.agents/skills/pest-testing/SKILL.md`
- Before pushing → `.agents/skills/git-push/SKILL.md`
```

### 7. Don't Do
The most valuable section. List the recurring mistakes:
- "Don't run `php artisan migrate:fresh` without `--seed` — auth tokens break"
- "Don't add npm packages without checking if Laravel Mix already provides it"
- "Don't edit `database/factories/UserFactory.php` — it's used by every test"

## Process

### Generating from scratch

1. **Read the README first.** Most projects have setup info there — extract it.
2. **Run `git ls-files | head -50`** to see the top-level shape.
3. **Read `composer.json`, `package.json`, `phpunit.xml`/`phpunit.dist.xml`, `Procfile`, `Dockerfile`** for the real stack.
4. **Run `php artisan list --no-ansi | grep -v "Available commands"` (or equivalent)** to see custom commands.
5. **Search the codebase for `// TODO` and `// HACK`** — those signal undocumented conventions.
6. **Draft the 7 sections above. Cap at 300 lines.**
7. **Show the user the draft. Ask: "Anything to add, anything to remove, anything wrong?"**
8. **Write the file. Commit with message `docs: add AGENTS.md for AI agent onboarding`.**

### Auditing an existing one

1. **Read the existing `AGENTS.md` end-to-end.**
2. **For every command listed: run it (or dry-run). Flag any that fail.**
3. **For every file path listed: verify it exists.**
4. **For every convention: spot-check 3 random files of that type. Does the rule hold?**
5. **Diff the listed stack versions against `composer.json` / `package.json` / lock files.**
6. **Report findings as a structured list. Recommend changes. Don't auto-edit.**

## Anti-Patterns

| Anti-pattern | Why it fails |
|---|---|
| 800-line `AGENTS.md` with full architecture | Tokens cost on every agent call. Move to `docs/architecture.md`. |
| Inlining `.env.example` contents | They drift. Link to the file instead. |
| "Run `make test`" when there's no Makefile | Aspirational instructions are worse than no instructions. |
| Tool-specific instructions ("Claude, do X") | Use `.cursor/rules/` or `CLAUDE.md`. `AGENTS.md` is the shared root. |
| Listing every directory under `app/` | Agents can `ls`. Document the *non-obvious* only. |
| Out-of-date version pins | Versions in `AGENTS.md` are advisory. Truth lives in `composer.json`. |

## Red Flags Found in Real Projects

- `AGENTS.md` says "PHP 8.1" but `composer.json` requires `^8.3`
- `AGENTS.md` lists a `bin/deploy` script that was deleted 8 months ago
- `AGENTS.md` says "use Pest" but half the test suite is still PHPUnit
- `AGENTS.md` doesn't mention the project uses Filament — agent generates raw Blade resources

When you find any of these, fix them in the same session.

## Integration

- **Called by:** `brainstorming` (when starting on a project that has no `AGENTS.md`); `using-superpowers` (when the agent notices the `AGENTS.md` is stale)
- **Calls:** None directly — but uses information from `composer.json`, `package.json`, lock files, and the existing skill catalog
- **Hand-off:** After generating, agents should re-read the new `AGENTS.md` before continuing the original task

## See Also

- [Sentry's canonical agents-md skill](https://officialskills.sh/getsentry/skills/agents-md) — this skill defers to that version when installed
- `.agents/skills/writing-skills/SKILL.md` — for skill files (which `AGENTS.md` should *link* to, not duplicate)

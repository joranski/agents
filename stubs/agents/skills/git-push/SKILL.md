---
name: git-push
description: Use when the user says "push", "commit", "git push", "deploy to git", or wants to send their code to origin — runs preflight (format + tests when available), generates commit message from diff, stages, commits, and pushes. Works on any git repo; adapts to Laravel, plain PHP, or git-only projects.
source: joranski/agents
canonical: true
---

# Git Push

Push code to origin with preflight checks and **agent-generated commit messages**.

**Core principle:** The agent writes the commit message from the diff — humans shouldn't describe what the code already says.

**Announce at start:** "I'm using the git-push skill to push your changes."

## Stack detection

Only run steps that exist — never fail because `artisan` or `pint` is missing.

| Signal | Enables |
|---|---|
| `git rev-parse --git-dir` | Entire skill (required) |
| `artisan` exists | `migrate:status`, `php artisan test` |
| `vendor/bin/pint` | Code formatting |
| `vendor/bin/pest` / `phpunit` | Tests when no `artisan` |

**Test runner priority:** `php artisan test` → `vendor/bin/pest` → `vendor/bin/phpunit` → warn and ask.

## Process

1. **Preflight** — branch, migrations (Laravel), Pint (`--dirty`), tests. Stop on test failure.
2. **Analyze** — `git status`, `git diff`, `git diff --cached` for commit message context.
3. **Generate message** — `<type>: <summary>` + body bullets grouped by purpose (not filename).
4. **Present** — show message; ask approval. Do NOT ask the user to write it.
5. **Stage, commit, push** — on approval. Rebase + re-test if push rejected.

Commands, message format, and flags: `.agents/skills/git-push/examples.md`

## Test skipping

Skip the full suite when local changes are **not PHP-relevant** (docs, CSS, images, etc.):

- No local changes → skip
- Only non-PHP paths (`.md`, `.json` config docs, assets) → skip
- Any `.php`, `composer.lock`, `config/`, `routes/`, `database/migrations/`, `phpunit.xml`, `pest.php` → **run tests**

Use `--force-tests` to override. Use `--skip-tests` to never run.

`php artisan git:push` applies this automatically. Test-runner details: `.agents/skills/_shared/test-runner.md`

## Common mistakes

- Asking the user to write the commit message — generate it yourself
- Running Laravel-only commands on non-Laravel repos
- Defaulting to `--parallel` for full-suite preflight — sequential by default
- One-liner messages for 3+ file changes — use body bullets

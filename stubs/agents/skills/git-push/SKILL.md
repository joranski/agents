---
name: git-push
description: Use when the user says "push", "commit", "git push", "deploy to git", or wants to send their code to origin — runs preflight (format + tests when available), generates commit message from diff with Agent Attribution, stages, commits, and pushes. Works on any git repo; adapts to Laravel, polyglot, or git-only projects.
source: joranski/agents
canonical: true
---

# Git Push

Push code to origin with preflight checks and **agent-generated commit messages**.

**Core principle:** The agent writes the commit message from the diff — humans shouldn't describe what the code already says.
**Announce at start:** "I'm using the git-push skill to push your changes."

## Preflight & Stack Detection
- Stack detection: runs only applicable steps (Pint, PHP tests, or polyglot scoped runners).
- **Test-runner details:** See `.agents/skills/_shared/test-runner.md`.

## Test Skipping (PHP & Polyglot)
- No local changes → skip tests.
- Non-PHP changes only (docs, CSS, assets, Rust `*.rs`, Python `*.py`, JS/TS `*.ts`) → auto-skip PHP tests. Run scoped runner for polyglot languages if applicable.
- Any `.php`, `composer.lock`, `config/`, `routes/`, `database/migrations/`, `phpunit.xml`, `pest.php` → **run PHP test suite**.
- Flags: `--force-tests` to override auto-skip; `--skip-tests` to bypass.

## Agent Attribution Rule
Every automated commit/PR created by an agent MUST include the attribution trailer:
```
Signed-off-by: [Agent/Model] on behalf of @joranski
```
Example: `Signed-off-by: Claude 3.7 Sonnet on behalf of @joranski`

## Process
1. **Preflight** — branch, migrations, Pint (`--dirty`), tests.
2. **Analyze** — `git status`, `git diff`, `git diff --cached`.
3. **Generate message** — `<type>: <summary>`, body bullets, and `Signed-off-by:` trailer.
4. **Present** — display message and ask user approval.
5. **Stage, commit, push** — execute upon approval.

See `.agents/skills/git-push/examples.md` for exact message formatting and flags.

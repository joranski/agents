---
name: git-push
description: Use when the user says "push", "commit", "git push", "deploy to git", or wants to send their code to origin — runs preflight (format + tests when available), generates commit message from diff, stages, commits, and pushes. Works on any git repo; adapts to Laravel, plain PHP, or git-only projects.
source: joranski/agents
canonical: true
---

# Git Push

## Overview

Push code to origin with automated preflight checks and **agent-generated commit messages**.

**Core principle:** The agent writes the commit message by analyzing the diff — humans shouldn't have to describe what the code already says.

**Announce at start:** "I'm using the git-push skill to push your changes."

## Stack Detection (Run First)

Before preflight, detect what this project supports. **Only run steps that exist** — never fail because `artisan` or `pint` is missing on a non-Laravel repo.

| Signal | Detect with | Enables |
|---|---|---|
| Git repo | `git rev-parse --git-dir` | Entire skill (required) |
| Laravel | `file_exists('artisan')` | `migrate:status`, `php artisan test` |
| Pint | `file_exists('vendor/bin/pint')` | Code formatting preflight |
| Pest (direct) | `file_exists('vendor/bin/pest')` | `vendor/bin/pest` when no `artisan` |
| PHPUnit (direct) | `file_exists('vendor/bin/phpunit')` | `vendor/bin/phpunit` when no `artisan` / `pest` |
| Node tests | `package.json` has `"test"` script | `npm test` (optional, ask user) |

**Default test runner priority:** `php artisan test` → `vendor/bin/pest` → `vendor/bin/phpunit` → skip (warn user).

**Default formatter:** `vendor/bin/pint` if present; otherwise skip (do not invent a formatter).

## The Process

### Step 1: Preflight Checks

Run applicable steps **in order**. Stop on test failure. Skip steps the project does not support.

```bash
# 1a. Branch (always)
git rev-parse --abbrev-ref HEAD

# 1b. Pending migrations (Laravel only — skip if no artisan)
php artisan migrate:status --no-ansi

# 1c. Pint (if vendor/bin/pint exists)
vendor/bin/pint --dirty --parallel --format agent

# 1d. Tests (pick first available runner; always use --parallel when supported)
# Laravel:
env -i PATH=$PATH HOME=$HOME php artisan test --compact --parallel

# Plain PHP (no artisan):
vendor/bin/pest --parallel
# or:
vendor/bin/phpunit
```

**Parallel flags:** Use `--parallel` on Pint and the test runner by default for speed. If parallel fails (old Pint, missing `brianium/paratest`, CI with 1 CPU), retry once without `--parallel` and note the fallback in your summary.

**Why `env -i` for `artisan test`?** Only when using `php artisan test`. The parent artisan process can leak env vars (`DB_CONNECTION`, `APP_ENV`) that override `phpunit.xml`. `env -i` strips inherited env so PHPUnit/Pest config is authoritative. Not needed for direct `vendor/bin/pest` invocations.

**If tests fail:** Show failures. Do NOT proceed. Help fix them if asked.

**If no test runner exists:** Warn once ("No test runner found — skipping tests") and ask the user to confirm before push. Do not silently skip.

### Step 2: Analyze Changes

Gather context for the commit message:

```bash
git status --porcelain
git diff
git diff --cached   # if something already staged
```

For untracked files, read enough content to describe them accurately (don't paste entire files into the commit message).

### Step 3: Generate Commit Message

Write a commit message following this format:

```
<type>: <concise summary line in imperative mood>

- <bullet describing a logical group of changes>
- <bullet describing another group>
```

**Type prefixes:** `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `style`

**Rules for the summary line:**
- Imperative mood: "Add X" not "Added X"
- Max 72 characters
- Capitalize first word after type prefix
- No period at end

**Rules for the body bullets:**
- Group related changes by purpose, not by filename
- Start each with a verb: Add, Fix, Update, Remove, Refactor
- Include WHY when not obvious from WHAT
- 3+ files changed → body bullets are expected, not optional

### Step 4: Present to User

Show the generated commit message and ask for approval:

```
Here's the commit message I've generated:

---
<the message>
---

Ready to stage, commit, and push? (Or tell me to adjust the message.)
```

**Do NOT ask the user to write the message.** Generate it yourself. They can tweak it.

### Step 5: Stage, Commit, Push

Once approved:

```bash
git add -A                    # or specific paths if user requested
git commit -m "$(cat <<'EOF'
<full message including body>
EOF
)"
git push origin <branch>
```

**If push fails** (rejected — remote ahead):

```bash
git pull --rebase origin <branch>
# Re-run tests with the same runner + --parallel used in Step 1
git push origin <branch>
```

## Quick Reference

| Step | Command | When | Stop on failure? |
|---|---|---|---|
| Branch | `git rev-parse --abbrev-ref HEAD` | Always | No |
| Migrations | `php artisan migrate:status --no-ansi` | Laravel only | Warn; ask to continue |
| Pint | `vendor/bin/pint --dirty --parallel --format agent` | If `vendor/bin/pint` exists | No (auto-fixes) |
| Tests (Laravel) | `env -i PATH=$PATH HOME=$HOME php artisan test --compact --parallel` | If `artisan` exists | **Yes** |
| Tests (Pest) | `vendor/bin/pest --parallel` | No artisan, pest exists | **Yes** |
| Tests (PHPUnit) | `vendor/bin/phpunit` | No artisan/pest, phpunit exists | **Yes** |
| Diff | `git status` + `git diff` | Always | No |
| Commit | `git commit` with generated message | Always | No |
| Push | `git push origin <branch>` | Always | Report error |

## Using `php artisan git:push`

Projects with `joranski/agents` installed can run the interactive pipeline locally:

```bash
php artisan git:push                   # Pint (--parallel) + tests (--parallel) + interactive stage/commit/push
php artisan git:push --skip-tests
php artisan git:push --skip-pint
```

The artisan command mirrors this skill's preflight defaults. Agents should still generate the commit message from the diff unless the user is running the command interactively (the command prompts for a message).

## Common Mistakes

**Asking the user to write the commit message**
- The agent generates it from the diff; only ask if they want edits

**Running Laravel-only commands on non-Laravel repos**
- No `artisan` → skip migrations and `artisan test`
- No `pint` → skip formatting; don't run phpcs/php-cs-fixer unless the project already uses them

**Skipping `env -i` when using `php artisan test`**
- Parent process env leaks break database config intermittently
- Not needed for direct `vendor/bin/pest`

**Pushing without any test run when a runner exists**
- Never skip if `artisan`, `pest`, or `phpunit` is available

**One-liner commit messages for multi-file changes**
- 3+ files → use body bullets grouped by purpose

**Ignoring parallel failures without retry**
- If `--parallel` errors, retry once without it before giving up

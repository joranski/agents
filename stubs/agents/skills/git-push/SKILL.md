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

# 1d. Tests (pick first available runner; **sequential by default** — parallel multiplies RAM)
# Laravel (default — reliable, one PHP process):
env -i PATH=$PATH HOME=$HOME php -d memory_limit=1G artisan test --compact --no-coverage

# Optional parallel (only when host has ~512M+ RAM per CPU core):
env -i PATH=$PATH HOME=$HOME php -d memory_limit=512M artisan test --compact --parallel --processes=2 --no-coverage

# Plain PHP (no artisan):
php -d memory_limit=1G vendor/bin/pest --compact --no-coverage
# or:
php -d memory_limit=1G vendor/bin/phpunit
```

**Parallel flags:** Use `--parallel` on Pint by default for speed. **Do not parallelize tests by default** — each worker is a full PHP process with its own `memory_limit`, so 8 cores × 1G ≈ 8GB RAM and often OOMs. Default test command is sequential. Only add `--parallel` when the machine has headroom; cap workers with `--processes=2` and use `512M` per worker, not `1G`.

**Why `env -i` for `artisan test`?** Only when using `php artisan test`. The parent artisan process can leak env vars (`DB_CONNECTION`, `APP_ENV`) that override `phpunit.xml`. `env -i` strips inherited env so PHPUnit/Pest config is authoritative. Not needed for direct `vendor/bin/pest` invocations.

**Why `-d memory_limit=1G` (sequential)?** Default PHP limits (often 128M) cause large Pest suites to die mid-run. One process at 1G is enough for most Laravel apps. Raising to 2G rarely makes sequential runs *faster* — it only helps completion when a single suite is genuinely heavy.

**Why not `--parallel --no-coverage` by default?** Parallel can be faster wall-clock time, but multiplies memory. Use it only when you have spare RAM. Skip coverage during local preflight regardless; collect in CI with `php artisan test --coverage`.

**When tests fail — diagnose before retrying blindly:**
1. Read the output for `Allowed memory size`, `Killed`, or `Out of memory`
2. Group failures by suite directory (`tests/Feature`, `tests/Unit`, `tests/Browser`, or `packages/*/tests`)
3. If memory + parallel → retry sequential: `php -d memory_limit=1G artisan test --compact --no-coverage`
4. If memory + sequential → set `<ini name="memory_limit" value="512M"/>` in `phpunit.xml` or try `-d memory_limit=2G`
5. Isolate heavy files: `php artisan test --compact tests/Feature/HeavyTest.php`
6. Run suites separately to find the bottleneck: `tests/Feature` then `tests/Unit`

**If tests fail:** Show failures and diagnostics. Do NOT proceed. Help fix them if asked.

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
# Re-run tests with the same mode used in Step 1 (sequential unless you opted into --parallel)
git push origin <branch>
```

## Quick Reference

| Step | Command | When | Stop on failure? |
|---|---|---|---|
| Branch | `git rev-parse --abbrev-ref HEAD` | Always | No |
| Migrations | `php artisan migrate:status --no-ansi` | Laravel only | Warn; ask to continue |
| Pint | `vendor/bin/pint --dirty --parallel --format agent` | If `vendor/bin/pint` exists | No (auto-fixes) |
| Tests (Laravel) | `env -i PATH=$PATH HOME=$HOME php -d memory_limit=1G artisan test --compact --no-coverage` | If `artisan` exists | **Yes** |
| Tests (Pest) | `php -d memory_limit=1G vendor/bin/pest --compact --no-coverage` | No artisan, pest exists | **Yes** |
| Tests (PHPUnit) | `php -d memory_limit=1G vendor/bin/phpunit` | No artisan/pest, phpunit exists | **Yes** |
| Diff | `git status` + `git diff` | Always | No |
| Commit | `git commit` with generated message | Always | No |
| Push | `git push origin <branch>` | Always | Report error |

## Using `php artisan git:push`

Projects with `joranski/agents` installed can run the interactive pipeline locally:

```bash
php artisan git:push                   # Pint (--parallel) + sequential tests + interactive stage/commit/push
php artisan git:push --skip-tests
php artisan git:push --skip-pint
php artisan git:push --parallel        # Opt-in parallel tests (needs ~512M RAM per CPU core)
php artisan git:push --no-parallel     # Disable parallel Pint
```

The artisan command runs tests **sequentially by default** and prints diagnostics (failing suite areas + memory/parallel suggestions) when tests fail. Pass `--parallel` only on hosts with spare RAM.

## Common Mistakes

**Asking the user to write the commit message**
- The agent generates it from the diff; only ask if they want edits

**Running Laravel-only commands on non-Laravel repos**
- No `artisan` → skip migrations and `artisan test`
- No `pint` → skip formatting; don't run phpcs/php-cs-fixer unless the project already uses them

**Skipping `env -i` when using `php artisan test`**
- Parent process env leaks break database config intermittently
- Always use: `env -i PATH=$PATH HOME=$HOME php -d memory_limit=1G artisan test --compact --no-coverage`
- Not needed for direct `vendor/bin/pest`

**Defaulting to `--parallel` for full-suite preflight**
- Parallel multiplies memory (cores × memory_limit). Often OOMs even at 1G per worker
- Default sequential; parallel is opt-in when RAM allows

**Ignoring parallel/memory failure diagnostics**
- On OOM/killed output, retry sequential before raising memory
- Group failures by `tests/Feature|Unit|Browser` and isolate heavy files

**Pushing without any test run when a runner exists**
- Never skip if `artisan`, `pest`, or `phpunit` is available

**One-liner commit messages for multi-file changes**
- 3+ files → use body bullets grouped by purpose

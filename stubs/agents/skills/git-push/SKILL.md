---
name: git-push
description: Use when the user says "push", "commit", "git push", "deploy to git", or wants to send their code to origin — runs preflight, generates commit message from diff, stages, commits, and pushes
---

# Git Push

## Overview

Push code to origin with automated preflight checks and **agent-generated commit messages**.

**Core principle:** The agent writes the commit message by analyzing the diff — humans shouldn't have to describe what the code already says.

**Announce at start:** "I'm using the git-push skill to push your changes."

## The Process

### Step 1: Preflight Checks

Run these in order. Stop on failure.

```bash
# 1a. Check branch
git rev-parse --abbrev-ref HEAD

# 1b. Check for pending migrations
php artisan migrate:status --no-ansi

# 1c. Run Pint (code formatting)
vendor/bin/pint --dirty --format agent

# 1d. Run tests in a clean env (CRITICAL)
env -i PATH=$PATH HOME=$HOME php artisan test --compact
```

**Why `env -i`?** The parent artisan process leaks env vars (`DB_CONNECTION=pgsql`, `APP_ENV=local`) that override `phpunit.xml`'s test config. `env -i` strips inherited env so `phpunit.xml`'s `<env>` tags are the sole config source.

**If tests fail:** Show failures. Do NOT proceed. Help fix them if asked.

### Step 2: Analyze Changes

Gather context for the commit message:

```bash
# See what's changed (modified + untracked)
git status --porcelain

# Get the actual diff for modified files
git diff

# For untracked files, show their content briefly
git diff --no-index /dev/null <file>  # or just describe new files
```

### Step 3: Generate Commit Message

Write a commit message following this format:

```
<type>: <concise summary line in imperative mood>

- <bullet describing a logical group of changes>
- <bullet describing another group>
- <bullet for any new files/features>
- <bullet for any fixes or removals>
```

**Type prefixes:** `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `style`

**Rules for the summary line:**
- Imperative mood: "Add X" not "Added X" or "Adding X"
- Max 72 characters
- Capitalize first word after type prefix
- No period at end

**Rules for the body bullets:**
- Group related changes into logical bullets
- Start each with a verb: Add, Fix, Update, Remove, Refactor, Extract, Migrate
- Be specific: mention file names, class names, or features
- Include the WHY when it's not obvious from the WHAT
- Don't list every file — group by purpose

**Quality examples from this repo:**

```
refactor: Consolidate AI skills into single .agents/ directory

- Merged 13 Superpowers workflow skills into .agents/skills/
- Moved .agent/rules/guidelines.md to .agents/rules/
- Deleted .agent/ (Superpowers npm package source)
- Deleted .cursor/skills/ (5 duplicate skills already in .agents/)

Before: 4 directories (.agent, .agents, .cursor/skills, .gemini)
After: 1 canonical location (.agents/) with 19 skills total
```

```
feat: Modernize frontend architecture and add Spanish localization

- Migrated frontend to Laravel Flux UI architecture with Tailwind CSS v4
- Implemented Spanish localization (es) using mcamara/laravel-localization
- Extracted corporate entity info into global config/company.php
- Added Feature tests for all localized public routes
```

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
# Stage all changes (or specific files if user requested)
git add -A

# Commit with the generated message
git commit -m "<message>"

# Push to origin
git push origin <branch>
```

**If push fails** (e.g., rejected due to remote changes):

```bash
git pull --rebase origin <branch>
# Re-run tests after rebase
env -i PATH=$PATH HOME=$HOME php artisan test --compact
git push origin <branch>
```

## Quick Reference

| Step | Command | Stop on failure? |
|------|---------|-----------------|
| Pint | `vendor/bin/pint --dirty --format agent` | No (auto-fixes) |
| Tests | `env -i PATH=$PATH HOME=$HOME php artisan test --compact` | **Yes** |
| Diff | `git diff` + `git status --porcelain` | No |
| Stage | `git add -A` | No |
| Commit | `git commit -m "<generated>"` | No |
| Push | `git push origin <branch>` | Report error |

## Common Mistakes

**Asking user to write the commit message**
- The whole point of this skill is that the agent generates it from the diff
- Only ask if they want to adjust your draft

**Running tests without `env -i`**
- Parent artisan process leaks `DB_CONNECTION=pgsql`, `APP_ENV=local`
- Tests silently run against production database and fail intermittently
- Always use: `env -i PATH=$PATH HOME=$HOME php artisan test --compact`

**One-liner commit messages for multi-file changes**
- If 3+ files changed, there should be body bullets
- Group by purpose, not by filename

**Pushing without running tests**
- Never skip tests, even if "it's just a docs change"
- Tests catch cascading failures you don't expect

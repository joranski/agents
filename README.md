# Joranski Agents

AI agent skills, smart deploy, and Night Shift automation for Laravel projects.

## Install

```bash
# Add the private repository (one-time)
composer config repositories.agents vcs https://github.com/joranski/agents.git

# Require the package
composer require joranski/agents --dev
```

## Setup

```bash
php artisan agents:install
```

The installer will:

1. **Prompt for credentials** — Filament Blueprint, Flux Pro, Anthropic API key (press Enter to skip any)
2. **Configure Supervisor** — sets `SUPERVISOR_WORKER` in `.env` for the deploy pipeline
3. **Publish 22 AI agent skills** → `.agents/skills/`
4. **Publish agent rules** → `.agents/rules/`
5. **Install Night Shift** → `bin/night-shift` (autonomous issue solver)
6. **Configure MCP** → `claude.json` + `.gemini/settings.json`
7. **Update `.gitignore`** with agent runtime files

### Re-run setup anytime

```bash
php artisan agents:install --setup    # Re-run credentials wizard only
php artisan agents:install --force    # Overwrite all published files
php artisan agents:install --skills   # Update skills only
```

## Commands

### `php artisan git:pull`

Smart deploy pipeline with 7 phases:

```
maintenance mode → pull → detect changes → composer/migrate/npm (only if needed)
→ permissions → cache clear/rebuild → worker restart → go live
```

```bash
php artisan git:pull                   # Interactive
php artisan git:pull --force           # Non-interactive (for automation/cron)
php artisan git:pull --skip=npm        # Skip specific steps
php artisan git:pull --skip=workers    # Skip worker restart
```

Reads `SUPERVISOR_WORKER` from `.env` to know which supervisor group to restart. Defaults to `{project-directory}-worker` if not set.

### `php artisan git:push`

Safe push pipeline with preflight checks:

```
pint (code style) → test suite → stage files → commit → push
```

```bash
php artisan git:push                   # Full pipeline
php artisan git:push --skip-tests      # Skip test suite
php artisan git:push --skip-pint       # Skip code formatting
```

### `php artisan agents:install`

Publish agent files into your project. Existing files are **never overwritten** unless `--force` is passed.

```bash
php artisan agents:install             # Install everything + credentials wizard
php artisan agents:install --setup     # Re-run credentials wizard only
php artisan agents:install --force     # Overwrite existing files
php artisan agents:install --skills    # Only install/update skills
```

## Environment Variables

Add to `.env` (the setup wizard handles these automatically):

```env
# Required for Night Shift autonomous agent
ANTHROPIC_API_KEY="sk-ant-..."

# Used by git:pull to restart the correct supervisor worker group
# Defaults to {project-directory-name}-worker if not set
SUPERVISOR_WORKER="your-project-worker"
```

## Night Shift

Autonomous AI agent that solves GitHub issues while you sleep.

```bash
# Manual run
cd /var/www/your-project && ./bin/night-shift

# Crontab (runs nightly at 2am)
0 2 * * * cd /var/www/your-project && ./bin/night-shift >> storage/logs/night-shift.log 2>&1
```

Requires: `ANTHROPIC_API_KEY` in `.env`, `gh` CLI authenticated, clean git working tree.

## Skills Included

| Skill | Purpose |
|-------|---------|
| `brainstorming` | Design before code — explores intent, requirements, and recommends a worktree strategy |
| `writing-plans` | Multi-step task planning with TDD steps, worktree strategy, and execution-mode hand-off |
| `using-git-worktrees` | Isolated feature work — invoked only when the plan recommends it (advisory) |
| `executing-plans` | Plan execution with batch checkpoints for human review |
| `single-flow-task-execution` | Plan execution with automated per-task two-stage review (default) |
| `finishing-a-development-branch` | Branch completion workflow — merge / PR / keep / discard |
| `using-superpowers` | Meta-skill: how to find and use skills |
| `writing-skills` | Creating new skills with TDD |
| `verification-before-completion` | Evidence before claims — no shortcuts |
| `systematic-debugging` | Root-cause analysis before proposing fixes |
| `test-driven-development` | Red-green-refactor discipline |
| `requesting-code-review` | Pre-merge structured review |
| `receiving-code-review` | Technical rigor on feedback, no performative agreement |
| `removing-dead-files` | Safe dead code removal (proof of death required) |
| `package-extraction-scout` | Identify mature services that should become standalone Composer packages |
| `git-push` | Agent-generated commit messages from diff analysis + preflight |
| `laravel-best-practices` | Laravel PHP code patterns (DB, security, eloquent, queues, etc.) |
| `pest-testing` | Pest PHP testing patterns, browser tests, datasets |
| `blueprint-code-review` | Filament v5 Blueprint standards audit |
| `fluxui-development` | Flux UI component development |
| `volt-development` | Single-file Livewire Volt components |
| `tailwindcss-development` | Tailwind CSS v4 utility patterns |

## Migrating from local commands

If your project already has `app/Console/Commands/GitPull.php` and `GitPush.php`, delete them after installing this package — the package provides them automatically via Laravel's package auto-discovery.

```bash
# 1. Install the package
composer require joranski/agents --dev

# 2. Add SUPERVISOR_WORKER to .env (replaces any hardcoded worker name)
echo 'SUPERVISOR_WORKER="your-project-worker"' >> .env

# 3. Remove local copies
rm app/Console/Commands/GitPull.php app/Console/Commands/GitPush.php

# 4. Verify
php artisan git:pull --help
```

## License

MIT

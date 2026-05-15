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
3. **Publish 22 AI agent skills** → `.agents/skills/` (with collision-safe upgrades — see below)
4. **Publish agent rules** → `.agents/rules/`
5. **Install Night Shift** → `bin/night-shift` (autonomous issue solver)
6. **Configure MCP** → `claude.json` + `.gemini/settings.json`
7. **Track ownership** → `.agents/.manifest.json` (gitignored) records which files we installed
8. **Update `.gitignore`** with agent runtime files

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

Publish agent files into your project with **smart collision handling** — your local edits and skills owned by other Composer packages (e.g. `laravel/boost`) are preserved automatically.

```bash
php artisan agents:install               # Install/upgrade safely (defers conflicts)
php artisan agents:install --setup       # Re-run credentials wizard only
php artisan agents:install --skills      # Only install/update skills
php artisan agents:install --force       # Also overwrite OUR skills you've edited locally
php artisan agents:install --force-all   # DANGEROUS: overwrite EVERYTHING, including
                                         # files owned by other packages
```

#### How collision handling works

Each skill carries `source:` and `canonical:` fields in its YAML frontmatter so the installer can tell who authored which file. A `.agents/.manifest.json` (gitignored) records SHA256 of every file we publish so we can detect local edits.

Decision matrix the installer applies to each file:

| Existing state | Default action | `--force` | `--force-all` |
|---|---|---|---|
| File missing | **install** | install | install |
| Identical to ours | **unchanged** | unchanged | unchanged |
| Owned by another package (`source:` differs) | **defer** (their territory) | defer | overwrite |
| Same source, but existing copy is `canonical: true` and ours is bundled (`canonical: false`) | **defer** (don't downgrade) | defer | overwrite |
| Our prior install, untouched on disk | **update** (silent upgrade) | update | update |
| Our prior install, locally edited | **preserve** (keep edits) | overwrite | overwrite |

**The 5 Laravel-canonical skills we bundle** (`laravel-best-practices`, `pest-testing`, `fluxui-development`, `volt-development`, `tailwindcss-development`) are marked `canonical: false` — if you also install `laravel/boost` or any other Laravel-skills publisher, their canonical version wins automatically. We ship them as a fallback so you get them even without Boost.

**Upgrading from v1.6.0 → v1.7.0:** Your existing skill files don't have the new frontmatter and there's no manifest yet, so on first install the installer will treat all 22 skills as "possibly hand-edited" and preserve them. Run once with `--force` to migrate:

```bash
composer update joranski/agents
php artisan agents:install --skills --force
```

After that one-shot, future upgrades are silent (`--force` no longer needed unless you actually edit a skill locally).

#### Install summary output

```
  install     5   new files written
  update     14   upgraded from prior install
  unchanged   2   already current
  preserved   1   local edits kept (re-run with --force to overwrite)
  deferred    5   existing canonical copy beats our bundled version

  Notices:
    • Preserved local edits in .agents/skills/brainstorming/SKILL.md (re-run with --force to overwrite)
    • Deferred to existing owner (laravel/skills): .agents/skills/pest-testing/SKILL.md
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

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
3. **Publish 26 AI agent skills** → `.agents/skills/` (with collision-safe upgrades — see below)
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
php artisan git:push                   # Full pipeline (Pint + tests use --parallel by default)
php artisan git:push --skip-tests      # Skip test suite
php artisan git:push --skip-pint       # Skip code formatting
php artisan git:push --no-parallel     # Disable parallel Pint/tests (older tooling or CI)
```

Tests run in a **clean environment** (`env -i`) so inherited `.env` values (e.g. `DB_CONNECTION`) do not override `phpunit.xml`. The suite uses `php -d memory_limit=1G artisan test --compact --parallel --no-coverage` for fast preflight — parallel ParaTest execution, no coverage collection (use CI for coverage reports).

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

**Bundled-but-deferred skills** are marked `canonical: false` so the upstream canonical version wins automatically when present:

- **5 Laravel-canonical** (`laravel-best-practices`, `pest-testing`, `fluxui-development`, `volt-development`, `tailwindcss-development`) — defer to `laravel/boost` or any other Laravel-skills publisher
- **2 Anthropic-canonical** (`webapp-testing`, `mcp-builder`) — defer to `@anthropics/skills` if installed
- **1 Sentry-canonical** (`agents-md`) — defers to Sentry's `@getsentry/skills` if installed

We ship all of them as fallback so you get them even without their upstream publisher.

**Upgrading from v1.8.x → v1.9.0:** v1.9.0 adds `harden-logic` (Railway-Oriented Programming + Specification Pattern + Finite State Machine scaffolding for stateful workflows) and wires it into `brainstorming`'s and `writing-plans`' decision flow. New files install fresh; updated planning skills will be deferred if you've edited them locally (re-run with `--force` only if you want our updates). If you're coming from v1.6.0 (pre-frontmatter), the original migration step still applies:

```bash
composer update joranski/agents
php artisan agents:install --skills --force   # one-time, only if upgrading from v1.6.0
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

### Process (planning & design)
| Skill | Purpose |
|-------|---------|
| `brainstorming` | Design before code — explores intent, requirements, and recommends a worktree strategy |
| `writing-plans` | Multi-step task planning with TDD steps, worktree strategy, and execution-mode hand-off |
| `using-git-worktrees` | Isolated feature work — invoked only when the plan recommends it (advisory) |

### Execution
| Skill | Purpose |
|-------|---------|
| `executing-plans` | Plan execution with batch checkpoints for human review |
| `single-flow-task-execution` | Plan execution with automated per-task two-stage review (default) |
| `finishing-a-development-branch` | Branch completion workflow — merge / PR / keep / discard |
| `git-push` | Agent-generated commit messages from diff analysis + preflight |

### Quality (review, debug, test)
| Skill | Purpose |
|-------|---------|
| `verification-before-completion` | Evidence before claims — no shortcuts |
| `systematic-debugging` | Root-cause analysis before proposing fixes |
| `test-driven-development` | Red-green-refactor discipline |
| `requesting-code-review` | Pre-merge structured review |
| `receiving-code-review` | Technical rigor on feedback, no performative agreement |
| `removing-dead-files` | Safe dead code removal (proof of death required) |
| `blueprint-code-review` | Filament v5 Blueprint standards audit |

### Architecture & meta
| Skill | Purpose |
|-------|---------|
| `using-superpowers` | Meta-skill: how to find and use skills |
| `writing-skills` | Creating new skills with TDD |
| `agents-md` | Generate and maintain `AGENTS.md` files (bundled, defers to `getsentry/skills`) |
| `package-extraction-scout` | Identify mature services that should become standalone Composer packages |
| `harden-logic` | Scaffold ROP + Specification + FSM architecture for stateful workflows (Result type, composable rule gates, guarded state transitions) |
| `mcp-builder` | Build Model Context Protocol servers (bundled, defers to `anthropics/skills`) |
| `webapp-testing` | Test web apps with Playwright at the agent level (bundled, defers to `anthropics/skills`) |

### Domain (Laravel)
| Skill | Purpose |
|-------|---------|
| `laravel-best-practices` | Laravel PHP code patterns (DB, security, eloquent, queues, etc.) — bundled, defers to `laravel/skills` |
| `pest-testing` | Pest PHP testing patterns, browser tests, datasets — bundled, defers to `laravel/skills` |
| `fluxui-development` | Flux UI component development — bundled, defers to `laravel/skills` |
| `volt-development` | Single-file Livewire Volt components — bundled, defers to `laravel/skills` |
| `tailwindcss-development` | Tailwind CSS v4 utility patterns — bundled, defers to `laravel/skills` |

See [CREDITS.md](CREDITS.md) for upstream attribution on bundled skills.

## Security & Operational Warnings

This package ships with several pieces that touch system-level concerns. Read this section before installing in production or shared environments.

### Night Shift uses reduced sandboxing

`stubs/bin/night-shift` invokes Claude Code with `--dangerously-skip-permissions`, which bypasses Claude Code's per-tool permission prompts. It also auto-pushes branches to your `origin` after each iteration.

**Run only when:**
- The repository is yours and you control its `origin` remote
- The host is dedicated (not a shared dev machine or CI runner with broader access)
- Your `ANTHROPIC_API_KEY` has a budget cap configured at console.anthropic.com
- You have monitoring on the resulting branches before any merge

If any of those isn't true, don't enable Night Shift via cron. Run it manually and inspect each branch.

### `git:pull` requires passwordless `sudo` and assumes Linux + nginx

The `git:pull` deploy pipeline runs:

```bash
sudo chown -R $USER:nginx storage/ bootstrap/cache/
sudo chmod -R 775 storage/ bootstrap/cache/
sudo supervisorctl restart <worker>:*
sudo systemctl reload php-fpm
```

**This will not work on:** macOS dev machines, FreeBSD, Windows, hosts without `nginx` group, hosts without supervisor, or any environment without passwordless sudo for the deploy user. It is a **Linux + nginx + php-fpm + supervisor** production-server tool.

For local dev: use `php artisan migrate`, `composer install`, etc. directly. `git:pull` is for `/var/www/...` style deploys.

### Credentials land in `auth.json` and `.env`

`agents:install` writes:
- **Filament Blueprint token** → `auth.json` (via `composer config --auth`)
- **Flux Pro token** → `auth.json` (via `composer config --auth`)
- **Anthropic API key** → `.env` as `ANTHROPIC_API_KEY=...`

`auth.json` should be in your `.gitignore` (Composer's default behavior, but verify). `.env` is already gitignored by every Laravel template. **Never commit either.**

### MCP configs include placeholder tokens

`stubs/mcp/gemini-settings.json` includes a `Bearer YOUR_FILAMENT_EXAMPLES_TOKEN` placeholder for the Filament Examples MCP server. Replace with your real token only locally — the file is published to your project, not your repo's tracked location, but be aware the placeholder is published as-is.

### Skills you bundle execute on your machine

When you `agents:install`, 25 skill files are written to `.agents/skills/` and become readable by any AI agent you point at the project. Skills cannot execute code on their own, but they can instruct an agent to run shell commands. Only install this package in projects you trust to run agent-generated code.

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

MIT — see [LICENSE](LICENSE).

## Credits

Built on top of [Superpowers](https://github.com/jessesquires/superpowers) (workflow skills), [Laravel](https://laravel.com) (Laravel-canonical skills), [Anthropic](https://officialskills.sh/anthropics) (`webapp-testing`, `mcp-builder`), and [Sentry](https://officialskills.sh/getsentry) (`agents-md`). See [CREDITS.md](CREDITS.md) for full attribution.

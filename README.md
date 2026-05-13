# Joranski Agents

AI agent skills, smart deploy, and Night Shift automation for Laravel projects.

## Install

```bash
composer require joranski/agents --dev
```

## Setup

```bash
php artisan agents:install
```

This publishes into your project:

| What | Where |
|------|-------|
| 21 AI agent skills | `.agents/skills/` |
| Agent rules | `.agents/rules/` |
| Night Shift agent | `bin/night-shift` |
| Claude MCP config | `claude.json` |
| Gemini MCP config | `.gemini/settings.json` |

## Commands

### `php artisan git:pull`

Smart deploy pipeline: maintenance mode → pull → detect changes → composer/migrate/npm only if needed → cache → workers → go live.

```bash
php artisan git:pull              # Interactive
php artisan git:pull --force      # Non-interactive (for automation)
php artisan git:pull --skip=npm   # Skip specific steps
```

### `php artisan git:push`

Safe push with preflight: pint → tests → stage → commit → push.

```bash
php artisan git:push              # Full pipeline
php artisan git:push --skip-tests # Skip test suite
```

### `php artisan agents:install`

Publish agent files into your project.

```bash
php artisan agents:install          # Install everything
php artisan agents:install --force  # Overwrite existing files
php artisan agents:install --skills # Only update skills
```

## Night Shift

Autonomous AI agent that solves GitHub issues while you sleep.

```bash
# Manual run
cd /var/www/your-project && ./bin/night-shift

# Crontab (runs nightly at 2am)
0 2 * * * cd /var/www/your-project && ./bin/night-shift >> storage/logs/night-shift.log 2>&1
```

## Skills Included

| Skill | Purpose |
|-------|---------|
| `brainstorming` | Design before code — explores intent and requirements |
| `git-push` | Agent-generated commit messages from diff analysis |
| `verification-before-completion` | Evidence before claims — no shortcuts |
| `systematic-debugging` | Root-cause analysis before proposing fixes |
| `pest-testing` | Pest PHP testing patterns and workflows |
| `test-driven-development` | Red-green-refactor discipline |
| `writing-plans` | Multi-step task planning before touching code |
| `blueprint-code-review` | Filament v5 Blueprint standards audit |
| `fluxui-development` | Flux UI component development |
| `volt-development` | Single-file Livewire Volt components |
| `tailwindcss-development` | Tailwind CSS utility patterns |
| `laravel-best-practices` | Laravel PHP code patterns |
| `using-superpowers` | Meta-skill: how to find and use skills |
| `single-flow-task-execution` | Structured task-by-task development |
| `executing-plans` | Implementation plan execution |
| `writing-skills` | Creating new skills with TDD |
| `finishing-a-development-branch` | Branch completion workflow |
| `requesting-code-review` | Pre-merge verification |
| `receiving-code-review` | Technical rigor on feedback |
| `removing-dead-files` | Safe dead code removal |
| `using-git-worktrees` | Isolated feature work |

## Environment

Add to your `.env` for full functionality:

```env
# Night Shift
ANTHROPIC_API_KEY="your-key"

# Deploy pipeline (git:pull)
SUPERVISOR_WORKER="your-project-worker"
```

## License

MIT

# Joranski Agents

AI agent skills, smart deploy, safe push, and Night Shift automation for Laravel and polyglot projects.

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

1. **Prompt for credentials** — Flux Pro, Anthropic API key (press Enter to skip any)
2. **Configure Supervisor** — sets `SUPERVISOR_WORKER` in `.env` for the deploy pipeline
3. **Publish 30 AI agent skills** → `.agents/skills/` (with collision-safe upgrades — see below)
4. **Publish agent rules** → `.agents/rules/` (including the Zero FilamentPHP rule)
5. **Install Night Shift** → `bin/night-shift` (autonomous issue solver with Brains-and-Hands Isolation)
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

Safe push pipeline with preflight checks and **Agent Attribution**:

```
pint (code style) → smart test runner → stage files → commit → push
```

```bash
php artisan git:push                   # Pint (--parallel) + smart test skip + interactive stage/commit/push
php artisan git:push --agent="Claude"  # Append Signed-off-by: [Agent] on behalf of @joranski
php artisan git:push --skip-tests      # Skip test suite
php artisan git:push --force-tests     # Run tests even for doc-only or non-PHP changes
php artisan git:push --skip-pint       # Skip code formatting
php artisan git:push --parallel        # Opt-in parallel tests (needs ~512M RAM per CPU core)
php artisan git:push --no-parallel     # Disable parallel Pint
```

Tests auto-skip when local changes are not PHP-relevant (docs, CSS, assets, or polyglot files like Rust/Python/Node). When polyglot files change without PHP files, a notice is displayed pointing to scoped runners in `.agents/skills/_shared/test-runner.md`. Tests run in a **clean environment** (`env -i`) so inherited `.env` values do not override `phpunit.xml`.

### `php artisan agents:install`

Publish agent files into your project with **smart collision handling** — your local edits and skills owned by other Composer packages (e.g. `laravel/boost`) are preserved automatically.

```bash
php artisan agents:install               # Install/upgrade safely (defers conflicts)
php artisan agents:install --setup       # Re-run credentials wizard only
php artisan agents:install --skills      # Only install/update skills
php artisan agents:install --force       # Also overwrite OUR skills you've edited locally
php artisan agents:install --force-all   # Overwrite EVERYTHING (including upstream canonicals)
```

## Night Shift

Autonomous AI agent that solves GitHub issues and remediates security vulnerabilities while you sleep.

```bash
# Manual run
cd /var/www/your-project && ./bin/night-shift

# Crontab (runs nightly at 2am)
0 2 * * * cd /var/www/your-project && ./bin/night-shift >> storage/logs/night-shift.log 2>&1
```

Features:
- **Brains-and-Hands Isolation:** Runs in a separate, isolated git worktree (`.worktrees/night-shift-...`).
- **Prompt Injection Defense:** Treats all issue bodies and external test outputs as untrusted data.
- **GitHub API Burst Throttling:** Capped at max 3 issue/PR actions per minute (20s sleep between calls) to prevent spam flags.
- **Daily Maintainer Digest:** Logs to `storage/logs/night-shift-digest-YYYYMMDD.md`, categorizing results into *Pearls Ready to Merge* vs *Chaff to Close*.

---

## Skills Included (30 Total)

### Process (Planning & Design)
| Skill | Purpose |
|-------|---------|
| `brainstorming` | Design before code — explores intent, requirements, and recommends worktree & architecture strategies |
| `writing-plans` | Multi-step task planning with TDD steps, Anti-Cheat Contract, and execution-mode hand-off |
| `voice-spec-ingestor` | Ingest raw 15-minute voice recordings/transcripts, normalize against `lexicon.json`, and synthesize 8-step plans |
| `using-git-worktrees` | Isolated feature work — invoked only when the plan recommends it (advisory) |

### Execution
| Skill | Purpose |
|-------|---------|
| `executing-plans` | Plan execution with batch checkpoints for human review |
| `single-flow-task-execution` | Plan-Once, Execute-Cheap ($550 -> $23) sequential execution with automated two-stage review |
| `finishing-a-development-branch` | Branch completion workflow — merge / PR / keep / discard |
| `git-push` | Agent-generated commit messages from diff analysis + preflight + Agent Attribution trailer |
| `night-shift` | Autonomous nocturnal issue solver and dependency auditor with worktree isolation |

### Quality (Review, Debug, Test)
| Skill | Purpose |
|-------|---------|
| `verification-before-completion` | Evidence before claims — requires surgical verification during iteration and full run before completion |
| `systematic-debugging` | Root-cause analysis before proposing fixes |
| `test-driven-development` | Red-green-refactor discipline |
| `code-review` | Pre-merge review with Surrounding Context Radius Auditing, Cross-Model Adversarial Verification, and Security Combo checks |
| `removing-dead-files` | Safe dead code removal (proof of death required) |
| `auto-research-optimizer` | Autonomous benchmark-driven optimization loop (baseline metric -> 1 hypothesis -> commit or `git checkout -- .`) |

### Architecture & Systems
| Skill | Purpose |
|-------|---------|
| `writing-skills` | Creating new skills with TDD and slim frontmatter architecture |
| `agents-md` | Generate and maintain ultra-compact `AGENTS.md` (<120 lines) following the 80% System Prompt Shrink Rule |
| `package-extraction-scout` | Identify mature services that should become standalone Composer packages |
| `harden-logic` | Scaffold ROP + Specification + FSM architecture for stateful workflows with mandatory 50% Complexity Cut Gate |
| `polyglot-architecture` | Standards for Phone Apps (SwiftUI/Compose/RN), Web, Systems (Rust/Go), and Python-to-Rust hot-path offload |
| `system-cli-and-bash` | DHH's Golden Bash Rule (explicit `if/then/fi`), cross-platform PowerShell/Bash, and malleable plugins |
| `mcp-builder` | Build Model Context Protocol servers (bundled, defers to `anthropics/skills`) |
| `webapp-testing` | Test web apps with Playwright at the agent level (bundled, defers to `anthropics/skills`) |

### UI & Media
| Skill | Purpose |
|-------|---------|
| `taste-differential-ui` | 3-Option Differential Taste Harness (Minimalist, Editorial, Opinionated behind `?variant=a|b|c`), then prune |
| `ai-media-pipeline` | Character/Style Bibles, Static Keyframe Pre-Validation before video rendering, and FFmpeg automation |

### Domain (Laravel & Web)
| Skill | Purpose |
|-------|---------|
| `laravel-best-practices` | Laravel PHP code patterns (DB, security, eloquent, queues, etc.) — bundled, defers to `laravel/skills` |
| `pest-testing` | Pest PHP testing patterns, surgical dirty-diff iteration, browser tests, datasets — bundled, defers to `laravel/skills` |
| `fluxui-development` | Flux UI component development — enforces Zero FilamentPHP standard with `flux-crud-stub.blade.php` |
| `volt-development` | Single-file Livewire Volt components — bundled, defers to `laravel/skills` |
| `tailwindcss-development` | Tailwind CSS v4 utility patterns — bundled, defers to `laravel/skills` |

---

## Major Changes in v2.0.0 (DHH Agentic Upgrade)

### 1. Complete FilamentPHP Purge (Zero Filament Standard)
All administrative interfaces, data tables, modals, and CRUD resources must be built directly with **Livewire/Volt + Flux UI + Tailwind CSS**. FilamentPHP dependencies, tokens, and MCP configurations have been completely removed from the package.

### 2. Multi-Tier Cost & Labor Reduction Protocols
- **Surgical Dirty-Diff Testing During Iteration (`_shared/test-runner.md`):** Agents are forbidden from running full test suites mid-loop on large projects. They must run dirty-diffs (`pest --dirty --stop-on-failure`) or single files with bail flags. Polyglot test runners for Rust, JS/TS, and Python are provided.
- **Plan-Once, Execute-Cheap Protocol:** Frontier reasoning models author an 8-step plan with an explicit **Anti-Cheat Contract** (forbidding empty wrappers or disabled assertions). Cost-effective worker models execute steps bite-by-bite and copy boilerplate from `assets/` via CLI rather than regenerating tokens.
- **Mandatory 50% Complexity Cut Gate (`harden-logic`):** Before completing stateful implementations, agents must review their diff and prune accidental complexity, merging single-use abstractions.
- **Agent Attribution Rule:** Automated commits include `Signed-off-by: [Agent/Model] on behalf of @joranski`.
- **Night Shift Hardening:** Isolated worktree execution, prompt injection defense on untrusted issues, burst throttling (max 3/min), and maintainer digest reporting.

---

## Security & Operational Warnings

- **Night Shift Sandboxing:** `bin/night-shift` isolates work in `.worktrees/` and auto-pushes branches to `origin`. Review branches before merging to main.
- **`git:pull` Production Target:** `git:pull` is configured for Linux + nginx + php-fpm + supervisor production servers. For local development, use native artisan commands.
- **Credentials Security:** Flux Pro tokens land in `auth.json` (ensure `auth.json` is gitignored). Anthropic keys land in `.env`.

## License

MIT — see [LICENSE](LICENSE).

## Credits

Built on top of [Superpowers](https://github.com/jessesquires/superpowers), [Laravel](https://laravel.com), [Anthropic](https://officialskills.sh/anthropics), and [Sentry](https://officialskills.sh/getsentry). See [CREDITS.md](CREDITS.md) for full attribution.

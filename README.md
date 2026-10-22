# Joranski Agents

AI agent skills, smart deploy, safe push, and Night Shift automation for Laravel, React, and polyglot projects.

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
3. **Publish 28 AI agent skills** → `.agents/skills/` (with collision-safe upgrades — see below), mirrored to `.claude/skills/` when the project uses Claude Code (`.claude/` or `CLAUDE.md` present)
4. **Publish agent rules** → `.agents/rules/` (including the Zero FilamentPHP and React/FluxUI co-equal standard)
5. **Install Night Shift** → `bin/night-shift` (autonomous issue solver with Brains-and-Hands Isolation)
6. **Configure MCP** → `.gemini/settings.json` (Boost writes `.mcp.json` for Claude Code and `.cursor/mcp.json` for Cursor)
7. **Track ownership** → `.agents/.manifest.json` (gitignored) records which files we installed
8. **Update `.gitignore`** with agent runtime files and an allowlist so only `.agents/rules/` and `.agents/skills/` are committed
9. **Check Laravel Boost** → offers to run `boost:install` when the project has no `boost.json`

### Laravel Boost is required

[Laravel Boost](https://github.com/laravel/boost) (a dependency of this package) owns the framework skills and guidelines — `laravel-best-practices`, `testing-best-practices`, `livewire-development`, `volt-development`, `fluxui-development`, `tailwindcss-development` — and writes them to `.cursor/skills/`, `.claude/skills/` and `AGENTS.md`. The skills here add workflow and architecture on top of Boost and never ship copies of Boost's skills. Run `php artisan boost:install` once per project (Boost only registers its commands when `APP_ENV=local` or `APP_DEBUG=true`).

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

Tests auto-skip when local changes are not PHP-relevant (docs, CSS, assets, or polyglot files like Rust/Python/Node/React). When polyglot files change without PHP files, a notice is displayed pointing to scoped runners in `.agents/skills/_shared/test-runner.md`. Tests run in a **clean environment** (`env -i`) so inherited `.env` values do not override `phpunit.xml`.

### `php artisan agents:install`

Publish agent files into your project with **smart collision handling** — your local edits and skills owned by other Composer packages (e.g. `laravel/boost`) are preserved automatically. Automatically prunes deprecated v1.x skills and stale filament configs.

```bash
php artisan agents:install               # Install/upgrade safely (defers conflicts, auto-prunes v1 skills)
php artisan agents:install --setup       # Re-run credentials wizard only
php artisan agents:install --skills      # Only install/update skills
php artisan agents:install --force       # Also overwrite OUR skills you've edited locally
php artisan agents:install --force-all   # Overwrite EVERYTHING (including upstream canonicals)
php artisan agents:install --no-prune    # Skip pruning deprecated skills and configs
```

### `php artisan agents:update`

One-step package update and asset refresh: checks composer constraints, runs `composer update joranski/agents`, runs `agents:install --force` with automatic deprecated skill and stale config pruning, then runs `boost:update` so Boost's framework skills are current too.

```bash
php artisan agents:update               # Update package + force-refresh skills & configs + boost:update
php artisan agents:update --no-composer # Refresh skills & configs without running composer update
php artisan agents:update --skills      # Update skills only
php artisan agents:update --no-boost    # Skip boost:update
```

## Upgrading from v1.x (^1.4) to v2.x

Upgrading from `v1.x` (`^1.4`) to `v2.x` represents a major version boundary with architectural consolidations. **`composer update` alone will not cross the `^1.x` → `^2.x` semver boundary without updating your `composer.json` constraint.**

### Step 1: Update the Package Constraint

```bash
composer require joranski/agents:^2.1 --dev
```
*(or omit `--dev` if installed in production)*

### Step 2: Run the Installer to Refresh & Prune

```bash
php artisan agents:install --force
# or
php artisan agents:update --no-composer
```

### What Happens Automatically:
- **Deprecated v1 Skill Pruning:** Automatically detects and removes retired v1.x skill directories from `.agents/skills/` (as well as `.cursor/skills/` and `.claude/skills/` if present):
  - `using-superpowers`
  - `requesting-code-review`
  - `receiving-code-review`
  - `blueprint-code-review`
  - Any stale skill directory matching `*filament*` — in `.cursor/skills/` and `.claude/skills/` only when this package installed it, so Boost's or Filament's own skills are left alone
- **Stale MCP Server Sanitization:** Scans `.gemini/settings.json`, `.mcp.json`, and `claude.json` to strip obsolete `filament-examples` entries while safely preserving all custom project MCP servers.
- **Manifest Cleanup:** Removes pruned entries from `.agents/.manifest.json` so upgrade tracking remains clean and accurate.
- **Fresh v2.1 Parity:** Deploys the complete suite of 32 modular skills with co-equal React & FluxUI first-class parity and pre-launch compliance audits.

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

## Skills Included (28 Total)

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

### Quality (Review, Debug, Test, Compliance)
| Skill | Purpose |
|-------|---------|
| `verification-before-completion` | Evidence before claims — requires surgical verification during iteration and full run before completion |
| `systematic-debugging` | Root-cause analysis before proposing fixes |
| `test-driven-development` | Red-green-refactor discipline |
| `code-review` | Pre-merge review with Surrounding Context Radius Auditing, Cross-Model Adversarial Verification, and Security Combo checks |
| `removing-dead-files` | Safe dead code removal (proof of death required) |
| `auto-research-optimizer` | Autonomous benchmark-driven optimization loop (baseline metric -> 1 hypothesis -> commit or `git checkout -- .`) |
| `web-compliance-audit` | **DORMANT** pre-launch audit for ADA Title III (WCAG 2.2 AA), Privacy/GPC, California Prop 65, and FTC Click-to-Cancel |

### Architecture & Systems
| Skill | Purpose |
|-------|---------|
| `writing-skills` | Creating new skills with TDD and slim frontmatter architecture |
| `agents-md` | Generate and maintain ultra-compact `AGENTS.md` (<120 lines) following the 80% System Prompt Shrink Rule |
| `package-extraction-scout` | Identify mature services that should become standalone Composer packages |
| `harden-logic` | Scaffold ROP + Specification + FSM architecture for stateful workflows with mandatory 50% Complexity Cut Gate |
| `polyglot-architecture` | Standards for Phone Apps (SwiftUI/Compose/RN), Web (React & FluxUI co-equal), Systems (Rust/Go), and Python offload |
| `system-cli-and-bash` | DHH's Golden Bash Rule (explicit `if/then/fi`), cross-platform PowerShell/Bash, and malleable plugins |
| `mcp-builder` | Build Model Context Protocol servers (bundled, defers to `anthropics/skills`) |
| `webapp-testing` | Test web apps with Playwright at the agent level (bundled, defers to `anthropics/skills`) |

### UI & Media
| Skill | Purpose |
|-------|---------|
| `taste-differential-ui` | 3-Option Differential Taste Harness in React or FluxUI (Minimalist, Editorial, Opinionated behind `?variant=a|b|c`), then prune |
| `ai-media-pipeline` | Character/Style Bibles, Static Keyframe Pre-Validation before video rendering, and FFmpeg automation |

### Domain (Laravel, React & Web)
| Skill | Purpose |
|-------|---------|
| `react-development` | Modern React 19+ / TypeScript development (Inertia v2 & Next.js) — co-equal with FluxUI with `react-crud-stub.tsx` |
| `flux-admin-crud` | Admin screens (table, search, modal forms) in Livewire/Volt + Flux — co-equal with React, enforces Zero FilamentPHP, Filament replacement guide and `flux-crud-stub.blade.php`. Uses Boost's `fluxui-development` for component APIs |

Framework skills (`laravel-best-practices`, `testing-best-practices`, `livewire-development`, `volt-development`, `fluxui-development`, `tailwindcss-development`) come from Laravel Boost.

---

## Major Changes in v2.2.0

- **Laravel Boost owns the framework skills.** Removed the bundled copies of `laravel-best-practices`, `fluxui-development`, `tailwindcss-development`, `volt-development` and `pest-testing`. They were overridden by Boost's copies in Cursor and Claude Code anyway. On upgrade, `agents:install` deletes untouched copies from `.agents/skills/`; copies you edited are kept and listed in the notices.
- **New `flux-admin-crud` skill** carries the Zero FilamentPHP admin-screen pattern, the Filament replacement guide, and `flux-crud-stub.blade.php` that used to live in the bundled `fluxui-development`.
- **Boost is part of setup.** `agents:install` offers to run `boost:install` when `boost.json` is missing; `agents:update` finishes with `boost:update` (skip with `--no-boost`).
- The surgical-testing Iron Law lives in `_shared/test-runner.md`; skills now point to Boost's `testing-best-practices` for Pest patterns.
- **Claude Code sees the skills.** Skills are mirrored to `.claude/skills/` when the project uses Claude Code.
- **Dropped the `claude.json` stub.** It pointed at a non-existent `php artisan mcp` command and Claude Code reads `.mcp.json` (written by Boost). Untouched copies are removed on upgrade.
- **`.gitignore` allowlist for `.agents/`** keeps agent scratch directories out of git; only `rules/` and `skills/` are committed.
- **Safer `*filament*` pruning.** Outside `.agents/skills/`, only skills this package installed are removed.

## Major Changes in v2.1.0 & v2.1.1

### 1. Automated v1-to-v2 Upgrade & Deprecated Skill Pruning (v2.1.1)
- **Automatic Pruning in `agents:install`:** Automatically removes obsolete v1 skill directories (`using-superpowers`, `requesting-code-review`, `receiving-code-review`, `blueprint-code-review`, and `*filament*`) from `.agents/skills/`, `.cursor/skills/`, and `.claude/skills/`.
- **MCP Config Sanitization:** Strips legacy `filament-examples` entries from `.gemini/settings.json`, `.mcp.json`, and `claude.json` while safely preserving custom user MCP servers.
- **New `php artisan agents:update` Command:** Single-command workflow that checks composer constraints, executes `composer update joranski/agents`, and force-refreshes installed stubs with automatic pruning.

### 2. React & FluxUI Co-Equal First-Class Parity (v2.1.0)
- **Co-Equal Frontend Stacks:** While maintaining the strict **Zero FilamentPHP** rule, projects can build rich frontends using either **React** (TypeScript + Tailwind CSS + Radix/Shadcn UI via Inertia.js v2, Next.js, or Vite) or **Livewire/Volt + FluxUI + Tailwind CSS**.
- **Auto-Detection:** Agents automatically detect `package.json` (React) or `composer.json` (`livewire/flux` or `livewire/volt`) and apply the matching skill.
- **Canonical React Starter:** Added `react-crud-stub.tsx` providing a full-featured, accessible data table with modal forms and `Result<T, E>` error handling.
- **Taste Differential UI Parity:** `taste-differential-ui` supports both React (`useSearchParams`) and Livewire/Volt.

### 3. Zero-Bloat Pre-Launch `web-compliance-audit` Gate (v2.1.0)
- **Strict Isolation:** Skill remains **DORMANT** during everyday feature development so token budgets and development speed are unaffected.
- **The 4 Lawsuit-Prevention Pillars:**
  1. **ADA Title III / WCAG 2.2 Level AA:** Focus traps, `aria-label` on icon buttons, live error announcements, skip links.
  2. **Privacy, Cookies & Global Privacy Control:** Auto-detects and honors browser `Sec-GPC: 1` (`navigator.globalPrivacyControl`), blocks non-essential trackers prior to consent.
  3. **Regional & Statutory Disclosures:** California Prop 65 Safe Harbor warning banner and FTC 1-click subscription cancellation parity.
  4. **Actionable Output:** Outputs clean remediation tables (`Severity | Pillar | File:Line | Exact Code Fix`) with auto-patching support.
- **Snippets Included:** Copy-paste-ready React (`.tsx`) and Blade/FluxUI (`.blade.php`) components in `assets/compliance-snippets.md`.

---

## Security & Operational Warnings

- **Night Shift Sandboxing:** `bin/night-shift` isolates work in `.worktrees/` and auto-pushes branches to `origin`. Review branches before merging to main.
- **`git:pull` Production Target:** `git:pull` is configured for Linux + nginx + php-fpm + supervisor production servers. For local development, use native artisan commands.
- **Credentials Security:** Flux Pro tokens land in `auth.json` (ensure `auth.json` is gitignored). Anthropic keys land in `.env`.

## License

MIT — see [LICENSE](LICENSE).

## Credits

Built on top of [Superpowers](https://github.com/jessesquires/superpowers), [Laravel](https://laravel.com), [Anthropic](https://officialskills.sh/anthropics), and [Sentry](https://officialskills.sh/getsentry). See [CREDITS.md](CREDITS.md) for full attribution.

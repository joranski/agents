# Joranski Agents

Lean, portable agent skills and universal engineering rules for Laravel + Livewire 4 + FluxUI Pro + Tailwind projects, plus an idempotent sync (`bin/sync-agents`), a smart deploy (`git:pull`), a safe push (`git:push`), and Night Shift. **Zero FilamentPHP.**

## Three layers

| Layer | Lives in | Owned by |
|-------|----------|----------|
| 1. Portable skills, universal rules, templates | this repo (`skills/`, `optional/`, `rules/`, `templates/`) | joranski/agents |
| 2. Thin host `AGENTS.md` (under 1,500 tokens) plus one-line `CLAUDE.md` (`@AGENTS.md`) | host repo root | the host |
| 3. Scoped host rules (`alwaysApply: false` plus globs) | host `.cursor/rules/*.mdc` | the host |

Laravel Boost owns the framework skills (`laravel-best-practices`, `livewire-development`, `fluxui-development`, `tailwindcss-development`, `testing-best-practices`, …). This package never bundles them, and the sync never touches them.

## Install

```bash
composer config repositories.agents vcs https://github.com/joranski/agents.git
composer require joranski/agents:^3.0 --dev
php artisan agents:install
```

`agents:install` runs the credentials wizard (Flux Pro and Anthropic keys), sets `SUPERVISOR_WORKER`, installs `bin/night-shift`, merges shared MCP servers into each editor's config (existing entries are never overwritten), makes `.claude/skills` a symlink to `.agents/skills`, syncs skills and rules through the engine below, then runs `boost:update` (`--no-boost` to skip). `agents:update` runs `composer update joranski/agents` first and then does the same.

## Sync skills and rules

`bin/sync-agents` is a framework-free PHP script. Run it from Composer (`vendor/bin/sync-agents`) or from a checkout (`~/packages/agents/bin/sync-agents --target=…`). It is safe to re-run; a second run changes nothing.

```bash
# Existing Laravel repos: preview, then apply
~/packages/agents/bin/sync-agents --target=/var/www/swm-app --dry-run
~/packages/agents/bin/sync-agents --target=/var/www/swm-app
~/packages/agents/bin/sync-agents --target=/var/www/mbi-app --with=git-push

# New repo: also scaffold AGENTS.md, CLAUDE.md and the ui-design rule (only if missing)
~/packages/agents/bin/sync-agents --target=~/code/new-app --init --profile=admin-only
```

| Flag | Effect |
|------|--------|
| `--dry-run` | Print the plan; write nothing |
| `--with=a,b` / `--without=a,b` | Opt optional skills in or out (remembered in the manifest) |
| `--ui-rule` / `--no-ui-rule` | Manage `.cursor/rules/ui-design.mdc`, or stop managing it |
| `--profile=storefront-admin\|admin-only` | Which ui-design template to use (implies `--ui-rule`) |
| `--init` | Create `AGENTS.md`, `CLAUDE.md` and the ui rule if missing |
| `--force` | Overwrite package files you edited locally (never host-owned files) |
| `--no-prune` | Keep files the package no longer ships |

What it does:

- **Syncs** active skills to `.agents/skills/` and rules to `.agents/rules/`. If `.claude/skills` is a real directory rather than a symlink, it is mirrored too.
- **Tracks ownership** in `.agents/.joranski-agents-manifest.json`, which stores a sha256 per file plus your `--with` and ui-rule choices. **Commit it**; the sync adds a `.gitignore` allow line when `/.agents/*` is ignored.
- **Prunes** files it installed that the package no longer ships, but only when they are untouched. Untouched copies from any earlier release are recognized through `known-hashes.json`, even in repos with no manifest. The legacy `.agents/.manifest.json` is imported and its skill entries retired. An untouched `.agents/rules/guidelines.md` is pruned in favor of `engineering.md`.
- **Keeps** package files you edited (notice: re-run with `--force`) and **skips** anything it did not install.
- **Never touches** Laravel Boost skills, `AGENTS.md`/`CLAUDE.md` in existing repos, `mcp.json`, `boost.json`, or any other `.cursor/rules/*.mdc` file. A host-written `ui-design.mdc` is never replaced.
- In Laravel repos, it ends with a reminder to run `php artisan boost:update`.

**Boost guidelines.** If Boost has `"guidelines": true`, `boost:update` rewrites `AGENTS.md` and `CLAUDE.md`. To keep the thin layer-2 files from `templates/`, set `"guidelines": false` in `boost.json` yourself. The sync only reminds you; it never edits `boost.json`.

## Skills

**Active (synced by default):**

| Skill | Use when |
|-------|----------|
| `grill-me` | A request is ambiguous; interview before planning |
| `writing-plans` | Multi-step work needs a written plan |
| `executing-plans` | Executing a written plan (checkpoint or review-per-task) |
| `test-driven-development` | Writing a feature or fix test-first (Pest) |
| `systematic-debugging` | A bug, failing test, or unexpected behavior |
| `verification-before-completion` | Before claiming work is done |
| `simplify-pass` | After a feature works: cut what isn't needed |
| `code-review` | Requesting or receiving a review |
| `second-opinion-review` | Adversarial pass on risky diffs (races, security, N+1, boundaries) |
| `harden-logic` | Stateful workflows that need explicit states and failure paths |
| `package-extraction-scout` | Spotting code ready to become a Composer package |
| `writing-skills` | Creating or editing a skill |

`_shared/test-runner.md` holds the surgical test commands the skills point to.

**Optional (`--with=`):** `git-push`, `night-shift`, `removing-dead-files` (these three have side effects and set `disable-model-invocation: true`), `agents-md`, `using-git-worktrees`, `react-development`, `webapp-testing`, `mcp-builder`, `web-compliance-audit`.

**Archived (never synced):** see [archive/README.md](archive/README.md) for what was retired and why.

**Rules:** `rules/engineering.md` (always on): stack, Zero Filament, package boundaries, discipline, database and git safety.

**Templates:** `templates/AGENTS.md`, `templates/CLAUDE.md`, `templates/cursor/ui-design.storefront-admin.mdc`, `templates/cursor/ui-design.admin-only.mdc`.

## Quarterly no-skills benchmark

Frontier models keep absorbing what skills teach. Once a quarter, check whether each skill still earns its tokens:

- [ ] Pick 5 representative tasks from recent work (a bug fix, a feature with tests, a refactor, a plan, a review).
- [ ] Run each task twice on the current default model in a scratch branch: once with `.agents/skills` present, once with it moved aside (keep `AGENTS.md` and rules identical).
- [ ] Record per run: tests passing, the diff size, the number of human corrections needed, the tokens and cost, and whether it verified before claiming done.
- [ ] For each skill that triggered, note whether the output differed in a way you'd keep.
- [ ] Move any skill with no measurable win for two quarters in a row to `optional/` or `archive/`. Tighten descriptions on skills that triggered when they shouldn't have.
- [ ] Re-run `php scripts/build-known-hashes.php`, bump the version, and log the result in `CHANGELOG.md`.

## Commands

### `php artisan git:pull`

Smart deploy: maintenance mode → pull → composer/migrate/npm only when needed → permissions → cache rebuild → worker restart → live.

```bash
php artisan git:pull                   # Interactive
php artisan git:pull --force           # Non-interactive (automation/cron)
php artisan git:pull --skip=npm        # Skip specific steps (also: --skip=workers)
```

Reads `SUPERVISOR_WORKER` from `.env` (default `{project-directory}-worker`).

### `php artisan git:push`

Pint → smart test runner → stage → commit → push.

```bash
php artisan git:push                   # Interactive
php artisan git:push --agent="Claude"  # Append Signed-off-by: [Agent] on behalf of @joranski
php artisan git:push --skip-tests      # Also: --force-tests, --skip-pint, --parallel, --no-parallel
```

Tests are skipped automatically when no PHP-relevant files changed. They run in a clean environment (`env -i`), so `.env` cannot override `phpunit.xml`.

### `php artisan agents:install` / `agents:update`

```bash
php artisan agents:install                    # Full install/upgrade
php artisan agents:install --skills           # Skills and rules only
php artisan agents:install --with=git-push    # Also sync optional skills
php artisan agents:install --profile=admin-only  # Manage ui-design.mdc with this profile
php artisan agents:install --force            # Overwrite package files you edited
php artisan agents:install --no-prune         # Keep files the package no longer ships
php artisan agents:update                     # composer update + install --force + boost:update
php artisan agents:update --no-composer       # Refresh without composer update
```

## Night Shift

`bin/night-shift` is an autonomous issue solver that works in an isolated git worktree (`.worktrees/night-shift-…`). It treats issue bodies as untrusted, throttles GitHub API calls, and writes a daily digest to `storage/logs/night-shift-digest-YYYYMMDD.md`.

```bash
0 2 * * * cd /var/www/your-project && ./bin/night-shift >> storage/logs/night-shift.log 2>&1
```

## Maintaining this package

- Run `php scripts/build-known-hashes.php` before every release, so the sync recognizes untouched copies of every published version.
- Run `tests/sync-agents-fixtures.sh` after changing the sync engine.
- New skills go in `skills/` only when they pass the quarterly benchmark bar. Otherwise they go in `optional/`.

## Security & operational notes

- Night Shift pushes branches to `origin`; review them before merging.
- `git:pull` targets Linux + nginx + php-fpm + supervisor production servers.
- Flux Pro tokens land in `auth.json` (keep it gitignored); Anthropic keys land in `.env`.

## License & credits

MIT. See [LICENSE](LICENSE), [CREDITS.md](CREDITS.md), and [CHANGELOG.md](CHANGELOG.md).

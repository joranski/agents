# Git Push — Examples & Reference

## Preflight commands

```bash
# 1a. Branch (always)
git rev-parse --abbrev-ref HEAD

# 1b. Pending migrations (Laravel only)
php artisan migrate:status --no-ansi

# 1c. Pint (if vendor/bin/pint exists)
vendor/bin/pint --dirty --parallel --format agent

# 1d. Tests — see .agents/skills/_shared/test-runner.md
env -i PATH=$PATH HOME=$HOME php -d memory_limit=1G artisan test --compact --no-coverage
```

## Analyze changes for commit message

```bash
git status --porcelain
git diff
git diff --cached
```

## Commit message format (with Agent Attribution)

```
<type>: <concise summary line in imperative mood>

- <bullet describing a logical group of changes>
- <bullet describing another group>

Signed-off-by: [Agent/Model] on behalf of @joranski
```

Types: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `style`

## Stage, commit, push

```bash
git add -A
git commit -m "$(cat <<'EOF'
feat: summary line

- First change group
- Second change group

Signed-off-by: Claude 3.7 Sonnet on behalf of @joranski
EOF
)"
git push origin <branch>
```

If push rejected (remote ahead):

```bash
git pull --rebase origin <branch>
# Re-run tests (same mode as Step 1)
git push origin <branch>
```

## `php artisan git:push` flags

```bash
php artisan git:push                   # Pint + smart test skip + interactive stage/commit/push
php artisan git:push --agent="Claude"  # Append Signed-off-by attribution trailer
php artisan git:push --skip-tests      # Never run tests
php artisan git:push --force-tests     # Run tests even for non-PHP changes
php artisan git:push --skip-pint
php artisan git:push --parallel        # Opt-in parallel tests
php artisan git:push --no-parallel     # Disable parallel Pint
```

## Quick reference

| Step | When | Stop on failure? |
|---|---|---|
| Branch | Always | No |
| Migrations | Laravel only | Warn; ask to continue |
| Pint | If `vendor/bin/pint` exists | No (auto-fixes) |
| Tests | PHP-relevant local changes | **Yes** |
| Diff | Always | No |
| Commit | Always | No |
| Push | Always | Report error |

## Test-runner details

See `.agents/skills/_shared/test-runner.md` for memory limits, parallel OOM diagnostics, and `env -i` rationale.

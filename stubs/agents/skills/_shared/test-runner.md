# Polyglot Test Runner & Surgical Iteration Reference

Use for iterative testing during development and full-suite preflight (`git:push`).

## Iron Law: Surgical Testing During Iteration
**Forbid running the full test suite mid-loop on large projects.**
During active implementation, run **ONLY** the targeted test file or dirty diff with `--stop-on-failure` / `--bail`. Pipe or filter output so passing test logs never bloat LLM context. Full-suite runs are reserved strictly for preflight before commit/push.

---

## 1. PHP / Pest / Laravel

### Surgical Iteration (Dirty & Targeted)
```bash
# Pest dirty diff (only tests related to changed files)
vendor/bin/pest --dirty --stop-on-failure --compact

# Targeted single file or filter
php -d memory_limit=1G artisan test --compact --bail tests/Feature/SpecificTest.php
php -d memory_limit=1G artisan test --compact --filter=testMethodName

# Plain PHP (no artisan)
php -d memory_limit=1G vendor/bin/pest --stop-on-failure tests/Feature/SpecificTest.php
```

### Full Suite Preflight (Sequential — Reliable)
Sequential execution at 1G memory avoids RAM multiplication from `--parallel`.
```bash
# Clean environment strips inherited DB_CONNECTION etc.
env -i PATH=$PATH HOME=$HOME php -d memory_limit=1G artisan test --compact --no-coverage

# Direct runner
php -d memory_limit=1G vendor/bin/pest --compact --no-coverage
```

### Opt-In Parallel Preflight
Only when host has 512MB+ free RAM per CPU core:
```bash
env -i PATH=$PATH HOME=$HOME php -d memory_limit=512M artisan test --compact --parallel --processes=2 --no-coverage
```

---

## 2. JavaScript / TypeScript / Mobile (React Native, Node, Web)

### Surgical Iteration
```bash
# Vitest (bail on first failure, targeted file)
npx vitest run path/to/file.test.ts --bail=1

# Jest (only changed files or single path)
npx jest --onlyChanged --bail
npx jest path/to/file.test.ts --bail

# React Native / Detox / Maestro
npx maestro test .maestro/flow-under-test.yaml
```

---

## 3. Rust (Systems, CLI, Hot-Path FFI)

### Surgical Iteration
```bash
# Run specific test by exact name with immediate abort
cargo test test_name -- --exact --nocapture

# Run tests in a specific package/file
cargo test -p my_crate --test integration_test -- --nocapture
```

---

## 4. Python (AI/ML, Microservices, Scripts)

### Surgical Iteration
```bash
# Pytest (stop on first failure, single file)
pytest tests/test_feature.py -x -q

# By keyword filter
pytest -k "test_method" -x -q
```

---

## Troubleshooting Memory & Failures
1. If parallel runs OOM: fall back to sequential immediately.
2. Group failures by suite/subsystem; run isolated heavy files individually.
3. Suppress passing clutter: use `--compact`, `-q`, or pipe `2>&1 | tail -n 25`.

# Test Runner Reference

**Iron law:** never run the full suite mid-iteration. Run the dirty diff or the one file you're working on, stop on the first failure, and keep passing output out of context. The full suite is for the completion check and pre-push only. A repo's `AGENTS.md` may pin exact CPU-capped commands; those win over this file.

## Iterating (Pest / Laravel)
```bash
vendor/bin/pest --dirty --stop-on-failure --compact
php -d memory_limit=1G artisan test --compact --bail tests/Feature/SpecificTest.php
php -d memory_limit=1G artisan test --compact --filter=it_rejects_empty_orders
vendor/bin/pint --dirty --format agent
```

## Full suite (completion / pre-push)
```bash
# env -i stops inherited DB_CONNECTION / APP_ENV from overriding phpunit.xml
env -i PATH=$PATH HOME=$HOME php -d memory_limit=1G artisan test --compact --no-coverage
```

Parallel only with ~512MB free RAM per worker, and capped:
```bash
env -i PATH=$PATH HOME=$HOME php -d memory_limit=512M artisan test --compact --parallel --processes=2 --no-coverage
```

## Front-end assets
```bash
npx vitest run path/to/file.test.ts --bail=1   # only if the repo has Vitest
npm run build                                  # proves Vite/Tailwind compile
```

## When runs fail
1. Out of memory in parallel: rerun sequentially.
2. Group failures by suite; run the heaviest file alone.
3. Keep output small: `--compact`, or `2>&1 | tail -n 40`.

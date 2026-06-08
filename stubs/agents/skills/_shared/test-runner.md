# Laravel / Pest Test Runner Reference

Use when running full-suite preflight (`git:push`, push skill) or troubleshooting memory/parallel failures.

## Full suite (default — reliable)

Sequential, no coverage. One PHP process at 1G avoids RAM multiplication from `--parallel`.

```bash
# Laravel (clean env — strips inherited DB_CONNECTION etc.)
env -i PATH=$PATH HOME=$HOME php -d memory_limit=1G artisan test --compact --no-coverage

# Plain PHP (no artisan)
php -d memory_limit=1G vendor/bin/pest --compact --no-coverage
php -d memory_limit=1G vendor/bin/phpunit
```

## Parallel (opt-in)

Only when host has ~512M+ free RAM **per CPU core**. Cap workers — do not use 1G per worker on many cores.

```bash
env -i PATH=$PATH HOME=$HOME php -d memory_limit=512M artisan test --compact --parallel --processes=2 --no-coverage
```

If parallel OOMs → fall back to sequential. Raising to 2G per worker usually makes things slower (swap), not faster.

## Targeted runs (never `--parallel`)

```bash
php -d memory_limit=1G artisan test --compact --filter=testName
php -d memory_limit=1G artisan test --compact tests/Feature/ExampleTest.php
```

## When tests fail with memory errors

1. Check if `--parallel` was used; retry sequential first
2. Group failures by suite (`tests/Feature`, `tests/Unit`, `tests/Browser`)
3. Run heavy files alone: `php artisan test --compact tests/Feature/HeavyTest.php`
4. Persist limit in `phpunit.xml`: `<php><ini name="memory_limit" value="512M"/></php>`

## Why `env -i` for `artisan test`?

Parent artisan can leak env vars (`DB_CONNECTION`, `APP_ENV`) that override `phpunit.xml`. Not needed for direct `vendor/bin/pest`.

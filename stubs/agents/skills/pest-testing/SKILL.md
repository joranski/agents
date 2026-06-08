---
name: pest-testing
description: "Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, or architecture tests. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code."
license: MIT
source: laravel/skills
canonical: false
bundled_by: joranski/agents
metadata:
  author: laravel
---

# Pest Testing 4

Use `search-docs` for detailed Pest 4 patterns.

## Creating tests

Use `php artisan make:test --pest {name}`. Do NOT prefix `Feature/` or `Unit/` in `{name}` — the flag handles the directory.

## Organization

- Feature/Unit: `tests/Feature`, `tests/Unit`
- Browser: `tests/Browser/`
- Do NOT remove tests without approval

## Conventions

- Match existing `test()` vs `it()` style in the same directory
- Use specific assertions: `assertSuccessful()` not `assertStatus(200)`
- Import mocks: `use function Pest\Laravel\mock;`
- Use datasets for repetitive validation tests
- Browser tests: `assertNoJavaScriptErrors()`, `RefreshDatabase`, factories

## Running tests

Full suite: sequential, 1G memory, no coverage. Parallel is opt-in. See `.agents/skills/_shared/test-runner.md`.

Targeted runs: `--filter` or single file — never `--parallel`.

## Pest 4 features

| Feature | Purpose |
|---------|---------|
| Browser Testing | Real browser integration tests |
| Smoke Testing | Validate multiple pages for JS errors |
| Architecture Testing | Enforce code conventions (`arch()`) |
| Visual Regression | Screenshot comparison |
| Test Sharding | Parallel CI runs |

Code examples: `.agents/skills/pest-testing/examples.md`

## Common pitfalls

- Not importing `Pest\Laravel\mock` before use
- `assertStatus(200)` instead of `assertSuccessful()`
- Deleting tests without approval
- Prefixing `Feature/` in `make:test --pest` name

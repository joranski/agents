---
name: pest-testing
description: "Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing, smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features."
license: MIT
source: laravel/skills
canonical: false
bundled_by: joranski/agents
metadata:
  author: laravel
---

# Pest Testing 4

Use `search-docs` for detailed Pest 4 patterns.

## Surgical Testing Iron Law
During active development loops, **NEVER run the full test suite**.
Run only dirty diffs (`vendor/bin/pest --dirty --stop-on-failure`) or single target files/filters. Pipe output to prevent context bloat. Full suite runs are for preflight only. Details: `.agents/skills/_shared/test-runner.md`.

## Creating Tests
Use `php artisan make:test --pest {name}`. Do NOT prefix `Feature/` or `Unit/` in `{name}` — the flag handles the directory.

## Organization & Rules
- Feature/Unit: `tests/Feature`, `tests/Unit`
- Browser: `tests/Browser/`
- Match existing `test()` vs `it()` style in the same directory
- Use specific assertions: `assertSuccessful()` not `assertStatus(200)`
- Import mocks: `use function Pest\Laravel\mock;`
- Browser tests: `assertNoJavaScriptErrors()`, `RefreshDatabase`, factories
- Do NOT remove tests without explicit approval

## Running Tests
- **Iteration:** `vendor/bin/pest --dirty --stop-on-failure` or `--filter=testName`
- **Preflight:** Sequential 1G clean env (`.agents/skills/_shared/test-runner.md`)

## Pest 4 Features & Examples
See `.agents/skills/pest-testing/examples.md` for:
- Basic & dataset tests
- Livewire Volt component tests
- Browser & smoke testing
- Architecture testing (`arch()`)

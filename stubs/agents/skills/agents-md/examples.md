# AGENTS.md Template & Multi-Agent Pointer Examples

## Minimal AGENTS.md Starter (<120 Lines)

```markdown
# Project Name

> Core application description in one sentence.

## Stack & Architecture
- **Backend:** PHP 8.4, Laravel 12
- **UI Architecture:** Livewire 4, Volt 1, Flux UI Pro, Tailwind CSS v4 (**Zero FilamentPHP standard**)
- **Data & Cache:** PostgreSQL 16, Redis 7
- **Testing:** Pest 4, Playwright

## Daily Commands
```bash
# Surgical iteration (never run full suite mid-loop):
vendor/bin/pest --dirty --stop-on-failure --compact
php -d memory_limit=1G artisan test --compact --bail tests/Feature/SpecificTest.php

# Code style:
vendor/bin/pint --dirty --format agent

# Safe preflight push:
php artisan git:push
```

## Conventions
1. **Zero FilamentPHP:** All admin views, tables, and modals use Livewire Volt + Flux UI (`<flux:table>`, `<flux:modal>`).
2. **ROP & Logic:** Stateful multi-step workflows use `harden-logic` `Result` pipelines (`app/Architecture/Support/Result.php`).
3. **Surgical Testing:** Run dirty diffs or single test methods during iteration; full suite runs only on push.

<!-- BOOST_START -->
## Laravel Boost Guidelines
- Use `search-docs` to retrieve accurate framework documentation.
- Leverage `database-schema` before generating database queries.
<!-- BOOST_END -->

## Agent Skills Reference
Skills live in `.agents/skills/`. Project-specific rules live in `.agents/rules/`.
- Implementation planning: `.agents/skills/writing-plans/SKILL.md`
- Execution: `.agents/skills/single-flow-task-execution/SKILL.md`
- Test runners: `.agents/skills/_shared/test-runner.md`
- Code review: `.agents/skills/code-review/SKILL.md`
- Push preflight: `.agents/skills/git-push/SKILL.md`
```

---

## 1-Line Tool Pointers

### `CLAUDE.md` (Project Root)
```markdown
See AGENTS.md for full project context, conventions, commands, and skills.
```

### `.cursorrules` / `.cursor/rules/agents.mdc`
```markdown
See AGENTS.md for full project context, conventions, commands, and skills.
```

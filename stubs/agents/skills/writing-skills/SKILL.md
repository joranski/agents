---
name: writing-skills
description: Use when creating new skills, editing existing skills, or verifying skills work before deployment
source: joranski/agents
canonical: true
---

# Writing Skills

A skill is a reusable technique, pattern, or reference that future agent sessions load on demand — not a story about how one problem was solved. Writing one is TDD for documentation (`.agents/skills/test-driven-development/SKILL.md`): watch an agent fail without it, write it, watch the agent comply.

## The Standard Layout
```
.agents/skills/<skill-name>/
  SKILL.md       # < 40 lines: when it applies, the rules, the steps. Loaded every time.
  examples.md    # Before/after code, templates, edge cases. Loaded only when implementing.
  assets/        # Copy-paste stubs and scripts (e.g. flux-admin-crud/assets/flux-crud-stub.blade.php)
```
Everything beyond the core rules goes into `examples.md` or `assets/` — `SKILL.md` competes for context on every task.

## Frontmatter
- `name`: lowercase letters, numbers, hyphens; verb-first or gerund (`root-cause-tracing`, `writing-plans`).
- `description`: starts with "Use when…", third person, lists triggers and symptoms only. **Never summarize the workflow** — agents follow the summary and skip the body.
- `source: joranski/agents` + `canonical: true` for skills this package owns; third-party skills keep their `source` and add `bundled_by: joranski/agents`.
- Framework skills (Laravel, Livewire, Volt, Flux, Tailwind, testing) belong to Laravel Boost — extend them, never copy them.

## Writing the Body
1. **RED:** run the task without the skill; record what the agent does wrong and its exact rationalizations.
2. **GREEN:** write the shortest `SKILL.md` that addresses those failures — one good example beats three languages.
3. **REFACTOR:** re-run; for every new loophole add an explicit counter or a "Stop Signals" bullet.
4. Reference other skills by path (`.agents/skills/<name>/SKILL.md`), never `@`-include them.

## Before Shipping
- `wc -l SKILL.md` is under 40; every command and path in it works.
- The description would make an agent pick this skill for the right task — and not for a wrong one.

Templates, description do/don't pairs, and the pressure-test checklist: `examples.md`. Full pressure-scenario method: `testing-skills-with-subagents.md`; general authoring guidance: `antigravity-best-practices.md`.

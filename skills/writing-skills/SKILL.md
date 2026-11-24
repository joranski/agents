---
name: writing-skills
description: "Use when creating, editing, or reviewing a SKILL.md in joranski/agents or a host repo. Do NOT use for AGENTS.md or .cursor/rules/*.mdc changes, or for one-off instructions that belong in a prompt."
source: joranski/agents
---

# Writing Skills

A skill earns its place only if an agent does the task worse without it. Frontier models already handle git, CLI habits, and framework conventions; don't restate those.

## Layout
```
skills/<name>/
  SKILL.md      # < 40 lines: when it applies, the rules, the steps
  examples.md   # optional: before/after code, templates
  assets/       # optional: copy-paste stubs and scripts
```

## Frontmatter
- `name`: lowercase-hyphenated, matches the folder.
- `description`: "Use when <trigger>. Do NOT use <wrong situations>." Triggers and exclusions only — never summarize the workflow, or the agent follows the summary and skips the body.
- `source: joranski/agents`.
- `disable-model-invocation: true` for skills with side effects (push, deploy, delete), so they run only when the user asks.
- Framework skills (Laravel, Livewire, Flux, Tailwind, Pest) belong to Laravel Boost; extend them, never copy them.

## Process
1. **Red:** run the task without the skill and note exactly what goes wrong.
2. **Green:** write the shortest skill that fixes those failures.
3. **Refactor:** re-run; close each new loophole with one explicit line.
4. Check the trigger doesn't collide with an existing skill's description.

## Placement
- `skills/`: active, synced to every repo. Keep it to ~15 skills.
- `optional/`: synced only with `bin/sync-agents --with=<name>`.
- `archive/`: history only, never synced.

Description do/don't pairs and templates: `examples.md`. Pressure-testing method: `testing-skills-with-subagents.md`.

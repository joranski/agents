# Writing Skills — Templates & Examples

## SKILL.md Template (< 40 lines)

```markdown
---
name: condition-based-waiting
description: Use when tests are flaky, pass alone but fail in the suite, or rely on sleeps and timeouts
source: joranski/agents
canonical: true
---

# Condition-Based Waiting

One sentence: what this is and the core principle.

## When It Applies
- Symptom 1, symptom 2 (the words an agent would search for: "flaky", "timeout", "race condition")
- Not for: the nearest case where it does NOT apply

## Rules
1. The rule, stated as an instruction.
2. The rule most likely to be skipped, with the reason it matters.

## Stop Signals
- The rationalization agents use to skip it → what to do instead.

Before/after code and edge cases: `examples.md`. Stub: `assets/<file>`.
```

## Descriptions: Triggers, Not Workflow

```yaml
# ❌ Summarizes the workflow — the agent follows this line and skips the body
description: Use when executing plans - runs tasks sequentially with a code review between tasks

# ❌ Vague, no trigger
description: For async testing

# ❌ First person
description: I can help you when your tests are flaky

# ❌ Names a technology the skill isn't specific to
description: Use when tests use setTimeout and are flaky

# ✅ Problem-shaped triggers only
description: Use when tests have race conditions, timing dependencies, or pass/fail inconsistently

# ✅ Technology-specific skill, explicit about it
description: Use when building an admin screen in a Livewire/Volt + Flux UI project
```

Real failure this prevents: a description saying "code review between tasks" made agents run one review, although the skill body required two (spec compliance, then code quality). Dropping the workflow from the description fixed it.

## What Goes Where

| Content | Location |
|---------|----------|
| Triggers, rules, numbered steps, stop signals | `SKILL.md` |
| Before/after code, full examples, edge cases | `examples.md` |
| Files to copy (Blade/TS stubs, scripts, config) | `assets/` |
| Command flags | the tool's `--help` — don't duplicate it |
| Framework API (Laravel, Livewire, Flux, Tailwind) | Laravel Boost skills + `search-docs` |

## Anti-Patterns
- **Narrative:** "In the 2025-10-03 session we found…" — rewrite as a rule plus one example.
- **Multi-language dilution:** `example.js`, `example.py`, `example.go` — keep one excellent example in the stack the skill targets.
- **Generic labels:** `helper1`, `step3` — name things by what they do.
- **Duplicating another skill:** reference `.agents/skills/<name>/SKILL.md` instead.

## Pressure-Test Checklist (discipline skills)

**RED**
- [ ] 3+ combined pressures (time, sunk cost, authority, exhaustion) in one scenario
- [ ] Run without the skill; record choices and rationalizations verbatim

**GREEN**
- [ ] Skill addresses exactly the recorded failures
- [ ] Same scenario with the skill — agent complies

**REFACTOR**
- [ ] Each new rationalization gets an explicit counter or stop signal
- [ ] Re-run until it holds

Scenario templates and the full method: `testing-skills-with-subagents.md`.

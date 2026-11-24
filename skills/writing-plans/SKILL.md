---
name: writing-plans
description: "Use when you have agreed requirements for a multi-step change (3+ files or several commits) and need a written implementation plan before touching code. Do NOT use for single-file fixes, questions, or when requirements are still unclear — run grill-me first."
source: joranski/agents
---

# Writing Plans

Write a plan a skilled engineer with zero context on this repo could execute. Save to `docs/plans/YYYY-MM-DD-<feature>.md`.

## Header (required)

```markdown
# <Feature> Plan
**Goal:** one sentence.
**Approach:** 2-3 sentences, including why the simpler alternative was rejected.
**Architecture:** vanilla Laravel | harden-logic (only for stateful multi-guard pipelines)
**Execution:** checkpoint (human review every ~3 tasks) | review-per-task (automated spec + quality review)
**Out of scope:** what this plan deliberately does not do.
```

## Tasks

Each task is small (one commit) and lists:
- **Files:** exact paths to create / modify (with line ranges) / test.
- **Steps:** failing test → run it (expected failure) → minimal implementation → run it (expected pass) → commit.
- **Commands:** exact test command from `.agents/skills/_shared/test-runner.md`, never the full suite mid-plan.

Write real code in the plan for anything non-obvious; "add validation" is not a step.

## Rules
- DRY, YAGNI, TDD, frequent commits. No speculative abstractions.
- Packages under `packages/` never import `App\*`; plan an interface or DTO instead.
- No hollow wrappers, no weakened or commented-out assertions.
- Reference skills by path (`.agents/skills/<name>/SKILL.md`), never `@`-include them.

## Handoff

Reply with the plan path, the Architecture and Execution choices with one-line reasons, then hand off to `.agents/skills/executing-plans/SKILL.md`.

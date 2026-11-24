---
name: simplify-pass
description: "Use when the user asks to simplify or de-bloat a change, and after finishing a plan, before the final review. Do NOT use to change behavior, add features, or refactor code outside the current diff."
source: joranski/agents
---

# Simplify Pass

Edit the current diff (`git diff origin/main...HEAD` plus uncommitted changes) like a senior editor. Behavior stays identical; tests stay green.

Cut:
- Interfaces, factories, DTOs, or helpers with a single caller and no transformation.
- Defensive checks for states the types or validation already rule out; try/catch that only rethrows or logs and continues.
- Options, flags, and config nobody passes; dead branches; commented-out code.
- Comments that narrate the code; duplicate logic that should reuse an existing helper.
- New abstractions where the surrounding code uses a simpler local pattern.

Process: list the proposed cuts first (file:line, why), apply them, run the surgical tests (`.agents/skills/_shared/test-runner.md`), and report the net lines removed. If a cut would change behavior or public API, leave it and mention it instead.

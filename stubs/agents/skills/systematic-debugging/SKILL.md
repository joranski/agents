---
name: systematic-debugging
description: Use when encountering any bug, test failure, or unexpected behavior, before proposing fixes
source: joranski/agents
canonical: true
---

# Systematic Debugging

**Iron Law: no fix without a confirmed root cause.** Guess-and-check fixes mask the real bug and create new ones. Use this especially under time pressure or after a fix already failed.

## 1. Read the Evidence First
- **Backend crash:** read the newest entry in `storage/logs/laravel.log` (exception, message, then the first `app/` frame in the trace) — or Boost's `last-error` / `read-log-entries` tools. Open that file and line before anything else.
- **Front-end crash:** when the `chrome-devtools` MCP server is configured, reproduce in the browser and read the console (source-mapped stack) and failed network requests; a 500 there leads back to `laravel.log`.
- Read the full error and stack trace; reproduce it reliably; check what changed (`git diff`, recent commits, config, dependencies).

## 2. Trace to the Source
- The error line is usually where bad data *explodes*, not where it *originates*. Trace callers upward until you find where the bad value is created, and fix there.
- For multi-layer failures, log what enters and exits each boundary once, then read where it first goes wrong.

## 3. One Hypothesis at a Time
- State it: "X is the root cause because Y." Make the smallest change that tests it — one variable only.
- Wrong? Form a new hypothesis; never stack fixes on top of each other. Don't understand something? Say so.

## 4. Fix and Prove It
1. Write a failing test that reproduces the bug (`.agents/skills/test-driven-development/SKILL.md`).
2. Apply a single fix at the source — no bundled refactors.
3. Run only the affected tests per `.agents/skills/_shared/test-runner.md`, then `.agents/skills/verification-before-completion/SKILL.md`.

## Stop Signals
- **3 failed fixes = an architecture problem.** Stop and discuss the design with the user before fix #4.
- "Quick fix for now", "just try X", "probably Y" — all mean: return to step 1.

Techniques (log-grep recipes, root-cause tracing, test-polluter bisection, defense in depth, condition-based waiting): `examples.md`. Polluter script: `assets/find-polluter.sh`.

---
name: harden-logic
description: "Use when designing a stateful Laravel workflow with 3+ side-effecting steps, explicit state transitions, and guard rules that must abort cleanly (subscription changes, fulfillment dispatch, payment retries, KYC). Do NOT use for CRUD, simple forms, single API calls, or anything under ~50 lines of vanilla Laravel."
source: joranski/agents
---

# Harden Logic

Railway Oriented Programming (`Result`), composable Specifications, and a backed-enum state machine for workflows that would otherwise become nested if/else.

## Fit check (all four must be true, otherwise use Form Requests + `DB::transaction()` and stop)
1. 3+ sequential steps.
2. Steps have side effects (DB writes, HTTP, queues).
3. A mid-pipeline failure must leave the database consistent.
4. The model has explicit states with defined transitions.

## Steps
1. **Bootstrap once per project:** if `app/Architecture/Support/Result.php` is missing, copy the six files in `assets/` (Result, SpecificationInterface, Abstract/And/Or/NotSpecification) verbatim, `php -l` them, and commit separately. Never edit them for a feature.
2. **Gather five names:** Domain, Model, ContextDto, RuleName, ActionName. If any is unclear, propose two options; never invent.
3. **State enum first:** show the transition table and get explicit confirmation before generating anything else.
4. **Generate in order**, `php -l` after each: DTO (hand-written `final readonly`), `State` enum, `Spec`, `Action`, and `Test` from `assets/templates/*.stub`. Replace every `{{placeholder}}`.
5. **TDD:** both the success track and the short-circuit test must pass (surgical run per `.agents/skills/_shared/test-runner.md`).
6. **Cut pass:** run `.agents/skills/simplify-pass/SKILL.md` on the diff; merge single-use classes and pass-through DTOs.
7. **Commit per layer:** enum + spec, then action + tests.

## Rules
- All mutations happen inside the `->through([...])` pipeline, wrapped in `DB::transaction()`.
- Guard every state change with `canTransitionTo()`; never use a `string $status`.
- A Spec is a gate, not the engine; split big rules and compose with `->and()`.
- Never `unwrap()` without checking `isSuccess()`.

Decision matrix, file map, worked example, and anti-patterns: `reference.md`.

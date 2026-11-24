---
name: verification-before-completion
description: "Use right before claiming work is done, fixed, or passing, and before committing, pushing, or opening a PR. Do NOT use mid-iteration as a reason to run the full suite — iterate with surgical tests instead."
source: joranski/agents
---

# Verification Before Completion

**Iron law: no success claim without fresh evidence from this turn.**

## Gate
1. **Identify** the command that proves the claim (tests, Pint, build, the original reproduction).
2. **Run** it fresh. During iteration: surgical (`.agents/skills/_shared/test-runner.md`). Before the completion claim: the full relevant suite.
3. **Read** the exit code and failure count, not just the tail.
4. **Report** the actual status with the evidence. If it failed, say so plainly.

| Claim | Needs | Not enough |
|---|---|---|
| Tests pass | Output showing 0 failures | An earlier run, "should pass" |
| Bug fixed | The original symptom now passes | Code changed |
| Regression test works | Watched it fail without the fix, pass with it | Passes once |
| Requirements met | Plan re-read line by line | Tests green |
| Subagent finished | `git diff` inspected | Its success report |

## UI changes
Confirm Livewire 4 + FluxUI Pro + Tailwind only (no Filament), no console errors, and that loading, empty, validation, and dark-mode states render.

## Stop signals
"Should", "probably", "looks right", or celebrating before running anything means: run the command first.

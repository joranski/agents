---
name: code-review
description: "Use when reviewing a branch or PR before merge, or when responding to review feedback someone else left. Do NOT use for a quick fresh-context bug hunt by another model (use second-opinion-review) or for trimming bloat (use simplify-pass)."
source: joranski/agents
---

# Code Review

## Requesting a review (before merge)
1. Get the range: `git diff --stat origin/main...HEAD`, then the full diff.
2. Fill in `.agents/skills/code-review/code-reviewer.md` with the requirement or plan and the range.
3. Read 3-5 untouched sibling files in the same domain so the review catches drift from local conventions, not just bugs in the hunks.
4. Check that `packages/` has no `App\*` imports and that no Filament code was introduced.
5. Classify findings as Critical (fix now), Important (fix before merge), or Minor (note). Give file:line, why it matters, and the fix.

## Receiving feedback
Verify before implementing; technical correctness over agreement.
1. Read all items. If any is unclear, ask about it before changing anything (items are often related).
2. Check each against the codebase: does it break callers, contradict an earlier decision, or add something unused (grep for callers before "implementing it properly")?
3. Push back with evidence when the suggestion is wrong for this codebase.
4. Implement in order: blocking bugs and security, simple fixes, then larger changes. Test each one.
5. Reply with what changed. No performative agreement ("You're absolutely right!", "Great point!").

GitHub inline replies: `gh api repos/{owner}/{repo}/pulls/{pr}/comments/{id}/replies`.

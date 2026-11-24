---
name: executing-plans
description: "Use when executing a written plan from docs/plans/ task by task. Do NOT use without a plan file (write one with writing-plans first) or for one-off edits that need no plan."
source: joranski/agents
---

# Executing Plans

1. **Load and challenge the plan.** Read it fully; raise gaps or contradictions before writing code.
2. **Never start on main/master** without explicit consent. Create a branch (a worktree only if the plan asks for one).
3. **Execute sequentially** in the mode the plan header names:
   - **checkpoint** — run ~3 tasks, report what changed plus verification output, say "Ready for feedback.", wait.
   - **review-per-task** — after each task, run a spec review (`spec-reviewer-prompt.md`), then a quality review (`code-quality-reviewer-prompt.md`). Fix findings before the next task. Implementer brief: `implementer-prompt.md`.
4. **Per task:** follow the steps exactly, TDD (`.agents/skills/test-driven-development/SKILL.md`), surgical test runs only (`.agents/skills/_shared/test-runner.md`), one commit.
5. **Finish:** full verification (`.agents/skills/verification-before-completion/SKILL.md`), then `.agents/skills/simplify-pass/SKILL.md` on the branch diff, then report and let the user choose merge / PR / keep.

## Stop and ask when
- A blocker appears (missing dependency, unclear instruction, unexpected test failure).
- The same verification fails twice.
- Following the plan would violate a rule in `.agents/rules/engineering.md`.

Don't guess through blockers, don't dispatch parallel coding agents, and don't silently expand scope.

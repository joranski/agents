---
name: executing-plans
description: Use when you have a written implementation plan with multiple batches and want checkpoint-style execution with human review between batches (3-task default). Prefer single-flow-task-execution for plans with independent tasks needing per-task review gates.
---

# Executing Plans

## Overview

Load plan, review critically, execute tasks in batches, report for review between batches.

**Core principle:** Batch execution with checkpoints for architect review.

**Announce at start:** "I'm using the executing-plans skill to implement this plan."

## When to Use This vs single-flow-task-execution

| Situation | Use this (`executing-plans`) | Use `single-flow-task-execution` |
|---|---|---|
| Tasks tightly related, want human checkpoint every ~3 tasks | ✓ | |
| Tasks fully independent, want automated per-task review gates | | ✓ |
| Architect wants to review direction mid-implementation | ✓ | |
| Long uninterrupted run preferred | | ✓ |
| Plan has < 3 tasks total | | ✓ |
| Plan has > 10 tasks and human attention is the bottleneck | ✓ | |
| Two-stage spec + quality review needed per task | | ✓ |

If unsure, default to `single-flow-task-execution`.

## The Process

### Step 1: Load and Review Plan

1. Read plan file
2. Review critically - identify any questions or concerns about the plan
3. If concerns: Raise them with your human partner before starting
4. **Check plan header for Worktree Strategy** — if recommended, invoke `.agents/skills/using-git-worktrees/SKILL.md` before any code changes
5. Update `<project-root>/docs/plans/task.md` (table-only tracker) and proceed

### Step 2: Execute Batch

**Default: First 3 tasks**

For each task:

1. Mark as in_progress
2. Follow each step exactly (plan has bite-sized steps)
3. Run verifications as specified
4. Mark as completed

### Step 3: Report

When batch complete:

- Show what was implemented
- Show verification output
- Say: "Ready for feedback."

### Step 4: Continue

Based on feedback:

- Apply changes if needed
- Execute next batch
- Repeat until complete

### Step 5: Complete Development

After all tasks complete and verified:

- Announce: "I'm using the finishing-a-development-branch skill to complete this work."
- **REQUIRED SKILL:** Use `.agents/skills/finishing-a-development-branch/SKILL.md`
- Follow that skill to verify tests, present options, execute choice

## When to Stop and Ask for Help

**STOP executing immediately when:**

- Hit a blocker mid-batch (missing dependency, test fails, instruction unclear)
- Plan has critical gaps preventing starting
- You don't understand an instruction
- Verification fails repeatedly

**Ask for clarification rather than guessing.**

## When to Revisit Earlier Steps

**Return to Review (Step 1) when:**

- Partner updates the plan based on your feedback
- Fundamental approach needs rethinking

**Don't force through blockers** - stop and ask.

## Remember

- Review plan critically first
- Follow plan steps exactly
- Don't skip verifications
- Reference skills when plan says to
- Between batches: just report and wait
- Stop when blocked, don't guess
- Never start implementation on main/master branch without explicit user consent
- Sequential execution only — do not dispatch parallel coding subagents

## Integration

**Required workflow skills:**

- **`.agents/skills/using-git-worktrees/SKILL.md`** - Set up isolated workspace when plan's Worktree Strategy recommends it
- **`.agents/skills/writing-plans/SKILL.md`** - Creates the plan this skill executes
- **`.agents/skills/finishing-a-development-branch/SKILL.md`** - Complete development after all tasks

**Alternative execution mode:**

- **`.agents/skills/single-flow-task-execution/SKILL.md`** - Per-task two-stage review instead of batch checkpoints (see decision matrix above)

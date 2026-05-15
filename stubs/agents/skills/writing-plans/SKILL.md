---
name: writing-plans
description: Use when you have a spec or requirements for a multi-step task, before touching code
---

# Writing Plans

## Overview

Write comprehensive implementation plans assuming the engineer has zero context for our codebase and questionable taste. Document everything they need to know: which files to touch for each task, code, testing, docs they might need to check, how to test it. Give them the whole plan as bite-sized tasks. DRY. YAGNI. TDD. Frequent commits.

Assume they are a skilled developer, but know almost nothing about our toolset or problem domain. Assume they don't know good test design very well.

**Announce at start:** "I'm using the writing-plans skill to create the implementation plan."

**Save plans to:** `docs/plans/YYYY-MM-DD-<feature-name>.md`

## Inputs from brainstorming

If `brainstorming` ran first, copy these forward into the plan header:

- Goal & architecture summary from the design doc
- **Worktree Strategy block** (Required / Strongly recommend / Skip) and reason
- Suggested branch name (if any)
- Link back to the design doc

If brainstorming did NOT run, decide the Worktree Strategy yourself using the same heuristics from `.agents/skills/brainstorming/SKILL.md` (Worktree Strategy table). Default for a multi-task plan: **Strongly recommend**.

## Bite-Sized Task Granularity

**Each step is one action (2-5 minutes):**
- "Write the failing test" - step
- "Run it to make sure it fails" - step
- "Implement the minimal code to make the test pass" - step
- "Run the tests and make sure they pass" - step
- "Commit" - step

## Plan Document Header

**Every plan MUST start with this header:**

```markdown
# [Feature Name] Implementation Plan

**Goal:** [One sentence describing what this builds]

**Architecture:** [2-3 sentences about approach]

**Tech Stack:** [Key technologies/libraries]

**Design doc:** `docs/plans/YYYY-MM-DD-<topic>-design.md` (if brainstorming ran)

## Worktree Strategy

**Recommendation:** <Required | Strongly recommend | Skip>
**Reason:** <one-line tied to a brainstorming heuristic>
**Suggested branch name:** `<feature/short-name>` (omit if Skip)

> Executors: if Required or Strongly recommend, invoke `.agents/skills/using-git-worktrees/SKILL.md` before Task 1. If Skip, work in the current branch and skip the worktree skill entirely.

## Execution Mode

**Recommended:** <single-flow-task-execution | executing-plans>
**Reason:** <why — see decision matrices in those skills>

---
```

## Task Structure

````markdown
### Task N: [Component Name]

**Files:**
- Create: `exact/path/to/file.py`
- Modify: `exact/path/to/existing.py:123-145`
- Test: `tests/exact/path/to/test.py`

**Step 1: Write the failing test**

```python
def test_specific_behavior():
    result = function(input)
    assert result == expected
```

**Step 2: Run test to verify it fails**

Run: `pytest tests/path/test.py::test_name -v`
Expected: FAIL with "function not defined"

**Step 3: Write minimal implementation**

```python
def function(input):
    return expected
```

**Step 4: Run test to verify it passes**

Run: `pytest tests/path/test.py::test_name -v`
Expected: PASS

**Step 5: Commit**

```bash
git add tests/path/test.py src/path/file.py
git commit -m "feat: add specific feature"
```
````

## Choosing the Execution Mode

In the plan header's "Execution Mode" line, pick one:

- **`single-flow-task-execution`** (default) — tasks are mostly independent, want automated per-task two-stage review (spec then quality), no human checkpoints between tasks
- **`executing-plans`** — tasks are tightly related, want human review every ~3-task batch, plan is large (>10 tasks)

See the decision matrices at the top of `.agents/skills/single-flow-task-execution/SKILL.md` and `.agents/skills/executing-plans/SKILL.md`.

## Remember
- Exact file paths always
- Complete code in plan (not "add validation")
- Exact commands with expected output
- Reference relevant skills with `.agents/skills/<name>/SKILL.md` paths (no `@` — that force-loads)
- DRY, YAGNI, TDD, frequent commits

## Execution Handoff

After saving the plan, hand off in this exact form:

> **Plan complete and saved to `docs/plans/<filename>.md`.**
>
> **Worktree Strategy:** `<Required | Strongly recommend | Skip>` — `<reason>`
>
> **Next step:** invoke `.agents/skills/<single-flow-task-execution|executing-plans>/SKILL.md` to execute. If Worktree Strategy is Required/Strongly recommend, that skill will invoke `.agents/skills/using-git-worktrees/SKILL.md` first.

Tracking:

- **Tracker file:** update `<project-root>/docs/plans/task.md` (table-only)
- **Per-task discipline:** `.agents/skills/test-driven-development/SKILL.md`
- **Final completion:** `.agents/skills/finishing-a-development-branch/SKILL.md`

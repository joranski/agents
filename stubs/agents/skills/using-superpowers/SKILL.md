---
name: using-superpowers
description: Use when starting any conversation - establishes how to find and use skills, requiring skill loading via view_file before ANY response including clarifying questions
---

<EXTREMELY-IMPORTANT>
If you think there is even a 1% chance a skill might apply to what you are doing, you ABSOLUTELY MUST invoke the skill.

IF A SKILL APPLIES TO YOUR TASK, YOU DO NOT HAVE A CHOICE. YOU MUST USE IT.

This is not negotiable. This is not optional. You cannot rationalize your way out of this.
</EXTREMELY-IMPORTANT>

## How to Access Skills

Skills installed by this package live at `.agents/skills/<skill-name>/SKILL.md` in the project root. Load them with whatever file-read tool your runtime exposes (`Read`, `view_file`, `cat`, etc.) and follow the contents directly.

Some runtimes also support a global skill location (e.g. `~/.gemini/skills/<skill-name>/SKILL.md` for Gemini, `~/.claude/skills/...` for Claude Code). Project-local always wins on naming collisions.

# Using Skills

## The Rule

**Invoke relevant or requested skills BEFORE any response or action.** Even a 1% chance a skill might apply means that you should invoke the skill to check. If an invoked skill turns out to be wrong for the situation, you don't need to use it.

```dot
digraph skill_flow {
    "User message received" [shape=doublecircle];
    "About to enter plan/design mode?" [shape=diamond];
    "Already brainstormed?" [shape=diamond];
    "Invoke brainstorming skill" [shape=box];
    "Might any skill apply?" [shape=diamond];
    "Load skill (Read/view_file)" [shape=box];
    "Announce: 'Using [skill] to [purpose]'" [shape=box];
    "Has checklist?" [shape=diamond];
    "Update project-root docs/plans/task.md per checklist item" [shape=box];
    "Follow skill exactly" [shape=box];
    "Respond (including clarifications)" [shape=doublecircle];

    "User message received" -> "About to enter plan/design mode?";
    "About to enter plan/design mode?" -> "Already brainstormed?" [label="yes"];
    "About to enter plan/design mode?" -> "Might any skill apply?" [label="no"];
    "Already brainstormed?" -> "Invoke brainstorming skill" [label="no"];
    "Already brainstormed?" -> "Might any skill apply?" [label="yes"];
    "Invoke brainstorming skill" -> "Might any skill apply?";

    "Might any skill apply?" -> "Load skill (Read/view_file)" [label="yes, even 1%"];
    "Might any skill apply?" -> "Respond (including clarifications)" [label="definitely not"];
    "Load skill (Read/view_file)" -> "Announce: 'Using [skill] to [purpose]'";
    "Announce: 'Using [skill] to [purpose]'" -> "Has checklist?";
    "Has checklist?" -> "Update project-root docs/plans/task.md per checklist item" [label="yes"];
    "Has checklist?" -> "Follow skill exactly" [label="no"];
    "Update project-root docs/plans/task.md per checklist item" -> "Follow skill exactly";
}
```

If the tracker file is missing, create `<project-root>/docs/plans/task.md` as a table-only task list.

## Red Flags

These thoughts mean STOP—you're rationalizing:

| Thought | Reality |
|---------|---------|
| "This is just a simple question" | Questions are tasks. Check for skills. |
| "I need more context first" | Skill check comes BEFORE clarifying questions. |
| "Let me explore the codebase first" | Skills tell you HOW to explore. Check first. |
| "I can check git/files quickly" | Files lack conversation context. Check for skills. |
| "Let me gather information first" | Skills tell you HOW to gather information. |
| "This doesn't need a formal skill" | If a skill exists, use it. |
| "I remember this skill" | Skills evolve. Read current version. |
| "This doesn't count as a task" | Action = task. Check for skills. |
| "The skill is overkill" | Simple things become complex. Use it. |
| "I'll just do this one thing first" | Check BEFORE doing anything. |
| "This feels productive" | Undisciplined action wastes time. Skills prevent this. |
| "I know what that means" | Knowing the concept ≠ using the skill. Invoke it. |

## Skill Priority

When multiple skills could apply, use this order:

1. **Process skills first** (`brainstorming`, `systematic-debugging`, `writing-plans`) - these determine HOW to approach the task
2. **Implementation skills second** (`fluxui-development`, `volt-development`, `tailwindcss-development`, `laravel-best-practices`, `pest-testing`, `blueprint-code-review`) - these guide execution
3. **Closeout skills last** (`verification-before-completion`, `requesting-code-review`, `finishing-a-development-branch`, `git-push`)

"Let's build X" → `brainstorming` first, then `writing-plans`, then implementation skills.
"Fix this bug" → `systematic-debugging` first, then domain-specific skills, then `verification-before-completion`.

## Skill Types

**Rigid** (TDD, debugging): Follow exactly. Don't adapt away discipline.

**Flexible** (patterns): Adapt principles to context.

The skill itself tells you which.

## User Instructions

Instructions say WHAT, not HOW. "Add X" or "Fix Y" doesn't mean skip workflows.

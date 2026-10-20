---
name: agents-md
description: "Generate, audit, and maintain ultra-compact AGENTS.md files at the repository root. Enforces the 80% System Prompt Shrink Rule (under 120 lines) to act as a high-signal router pointing to skills and shared runners, with protected Boost markers, 1-line tool pointers, and Zero FilamentPHP."
license: MIT
source: getsentry/skills
canonical: false
bundled_by: joranski/agents
metadata:
  author: sentry
  upstream: https://officialskills.sh/getsentry/skills/agents-md
---

# AGENTS.md

## The 80% System Prompt Shrink Rule
`AGENTS.md` is loaded into every agent context on every turn. **Target: under 120 lines total.**
Treat `AGENTS.md` strictly as a high-signal router pointing to `.agents/skills/` and `_shared/` rules — do NOT inline massive instructions, full directory listings, or lengthy API references.

## Key Standards
1. **Router Architecture:** Link to `.agents/skills/<name>/SKILL.md` and `.agents/skills/_shared/test-runner.md`.
2. **Protected Boost Markers:** Preserve `<!-- BOOST_START --> ... <!-- BOOST_END -->` blocks untouched so automated Boost updates never clobber custom project conventions.
3. **1-Line Tool Pointers:** Support `CLAUDE.md` and `.cursorrules` with 1-line references pointing directly to `AGENTS.md`.
4. **Zero FilamentPHP:** Mandate Livewire/Volt + FluxUI + Tailwind CSS for all administrative UIs, forms, and data tables.

## Required Sections (<120 Lines Total)
1. **Identity & Stack:** Project purpose, PHP/Laravel versions, Livewire/Volt/FluxUI, DB, and test framework.
2. **Setup & Daily Commands:** 4–6 commands max (surgical tests, Pint, push).
3. **Conventions:** 3–5 non-obvious rules (Zero FilamentPHP, ROP Result pipelines, surgical testing).
4. **Boost Block:** Wrapped inside `<!-- BOOST_START -->` / `<!-- BOOST_END -->`.
5. **Skill Pointers:** Links to key skills (planning, execution, review, push).

Templates and 1-line tool pointer stubs: `.agents/skills/agents-md/examples.md`

## Auditing AGENTS.md
- Test every command listed (or dry-run).
- Verify every file path exists.
- Enforce <120 line ceiling: move overflowing details into `docs/` or individual skill `examples.md`.

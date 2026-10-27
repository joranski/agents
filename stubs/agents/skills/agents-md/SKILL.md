---
name: agents-md
description: "Generate, audit, and maintain ultra-compact AGENTS.md files (<120 lines). Acts as a high-signal router pointing to skills and shared runners, with protected Boost markers, 1-line tool pointers, and Zero FilamentPHP (FluxUI default, React for client-heavy and mobile)."
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
2. **Protected Boost Markers:** Preserve `<!-- BOOST_START --> ... <!-- BOOST_END -->` blocks untouched.
3. **1-Line Tool Pointers:** Support `CLAUDE.md` and `.cursorrules` with 1-line pointers to `AGENTS.md`.
4. **Zero FilamentPHP & UI Hierarchy:** Livewire/Volt + FluxUI is the default; list React (TS + Radix/Shadcn + Tailwind) only when `package.json` has `react`, the user asked for React / Next.js / React Native, or a feature needs heavy client-side state (see `.agents/rules/guidelines.md`).
5. **Testing Override:** Never run full test suites mid-iteration; strictly follow `.agents/skills/_shared/test-runner.md` (`pest --dirty --stop-on-failure --compact` or single-file runners).

## Required Sections (<120 Lines Total)
1. **Identity & Stack:** Purpose, framework/runtime, UI stack (React or Livewire/Flux), DB, test framework.
2. **Setup & Daily Commands:** 4–6 commands max (surgical tests, Pint, push).
3. **Conventions:** 3–5 non-obvious rules (Zero FilamentPHP, ROP Result pipelines, surgical testing).
4. **Boost Block:** Wrapped inside `<!-- BOOST_START -->` / `<!-- BOOST_END -->`.
5. **Skill Pointers:** Links to key skills (planning, execution, review, push).

Templates and 1-line tool pointer stubs: `.agents/skills/agents-md/examples.md`

## Auditing AGENTS.md
- Test every command listed (or dry-run) and verify every path exists.
- Enforce <120 line ceiling: move overflowing details into `docs/` or individual skill `examples.md`.

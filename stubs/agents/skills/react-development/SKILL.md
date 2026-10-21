---
name: react-development
description: "Use for modern React 19+ / TypeScript development in Inertia.js v2, Next.js, or Vite. Enforces Zero FilamentPHP (co-equal with FluxUI), declarative dumb components wired to harden-logic Result.ts pipelines, zero useEffect waterfalls, accessible Radix/Shadcn + Tailwind primitives, and surgical Vitest/Playwright tests."
source: joranski/agents
canonical: true
---

# React Development

Co-equal first-class frontend stack with FluxUI. Enforces **Zero FilamentPHP**: build all admin interfaces, data tables, modals, and forms directly with modern React + TypeScript + Tailwind CSS.

## Core Rules
1. **Auto-Detection:** Trigger when `package.json` contains `react`.
2. **Dumb Components + ROP Pipelines:** Keep components declarative; wire actions to `harden-logic` `Result.ts` pipelines.
3. **No `useEffect` Waterfalls:** Derive state synchronously or fetch through server components / Inertia loaders.
4. **Accessible Primitives:** Build with Radix UI / Shadcn + Tailwind CSS v4. Ensure keyboard traps and ARIA labels.

## Canonical Assets & Patterns
- Canonical CRUD starter: `assets/react-crud-stub.tsx` (matches `flux-crud-stub.blade.php`).
- Inertia v2 & Next.js patterns: `examples.md`.

## Surgical Testing
- Run only targeted tests during iteration: `npx vitest run <file> --bail=1` (`.agents/skills/_shared/test-runner.md`).
- Never run full test suites mid-loop on large applications.

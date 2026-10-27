---
name: react-development
description: "Use for React 19+ / TypeScript work in Inertia.js v2, Next.js, Vite, or React Native — when package.json already requires react, the user explicitly asks for React / Next.js / React Native, or a feature needs heavy 60fps client-side state (canvas, timeline or video editors, node graphs, offline-first storage). Not the default for Laravel web UI; that is Livewire/Volt + FluxUI."
source: joranski/agents
canonical: true
---

# React Development

The **specialized track**, fully first-class when it applies. Livewire + Volt + FluxUI is the default for Laravel web apps (`.agents/rules/guidelines.md`); **Zero FilamentPHP** holds on both tracks.

## When React Is the Right Call
1. `package.json` already requires `react` — follow the existing stack.
2. The user explicitly asks for React, Next.js, or React Native (phone apps).
3. The feature needs heavy 60fps client-side state where server round-trips don't fit: canvas / timeline / video editors, drag-and-drop node graphs, offline-first local storage.

Otherwise build it with `flux-admin-crud` + Boost's `fluxui-development`. In a FluxUI app, a client-heavy feature can be one React island; the rest of the app stays Livewire.

## Core Rules
1. **Dumb Components + ROP Pipelines:** Keep components declarative; wire actions to `harden-logic` `Result.ts` pipelines.
2. **No `useEffect` Waterfalls:** Derive state synchronously or fetch through server components / Inertia loaders.
3. **Accessible Primitives:** Build with Radix UI / Shadcn + Tailwind CSS v4. Ensure focus management and ARIA labels.

## Canonical Assets & Patterns
- CRUD starter: `assets/react-crud-stub.tsx` (mirrors `flux-crud-stub.blade.php`).
- Inertia v2 & Next.js patterns: `examples.md`.

## Surgical Testing
- Iterate with targeted tests only: `npx vitest run <file> --bail=1` (`.agents/skills/_shared/test-runner.md`).
- Never run full test suites mid-loop.
